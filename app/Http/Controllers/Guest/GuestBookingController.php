<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;

class GuestBookingController extends Controller
{
    /**
     * Calculate quote for a potential booking
     */
    public function quote(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer',
            'room_type_id' => 'required|integer',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
        ]);

        // Note: Real implementation would calculate prices from pricing_rules and daily_availability_price
        // This is a placeholder for the checkout flow to consume
        return response()->json([
            'message' => 'Quote calculated successfully',
            'quote' => [
                'property_id' => $validated['property_id'],
                'room_type_id' => $validated['room_type_id'],
                'nights' => \Carbon\Carbon::parse($validated['check_out_date'])->diffInDays(\Carbon\Carbon::parse($validated['check_in_date'])),
                'base_price' => 100.00,
                'taxes' => 10.00,
                'total_price' => 110.00,
            ]
        ]);
    }

    /**
     * Store a new booking directly from the guest booking engine
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'ic_passport_no' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'guests' => 'required|integer|min:1',
            'total_price' => 'required|numeric',
            'rooms' => 'required|array|min:1',
            'rooms.*.property_id' => 'required|integer|exists:properties,id',
            'rooms.*.room_type_id' => 'required|integer|exists:room_types,id',
            'rooms.*.nights' => 'required|integer|min:1',
            'rooms.*.raw_price' => 'required|numeric',
            'rooms.*.price' => 'required|numeric',
        ]);

        try {
            $result = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
                
                // Get the first available hosting company from properties to link the guest to
                // Typically you'd get this from the first room's property, but we'll fetch it dynamically
                $firstProperty = \App\Models\Property::find($validated['rooms'][0]['property_id']);
                $hostingCompanyId = $firstProperty ? $firstProperty->hosting_company_id : null;

                // 1. Manage Guest Profile
                $guest = \App\Models\Guest::updateOrCreate(
                    ['email' => $validated['email']],
                    [
                        'first_name' => $validated['first_name'],
                        'last_name' => $validated['last_name'],
                        'phone_number' => $validated['phone'],
                        'ic_passport_no' => $validated['ic_passport_no'] ?? null,
                        'address' => $validated['address'] ?? null,
                        'city' => $validated['city'] ?? null,
                        'state' => $validated['state'] ?? null,
                        'nationality' => $validated['nationality'] ?? null,
                        'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                        'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
                        'hosting_company_id' => $hostingCompanyId
                    ]
                );

                // Try to find a default channel for "Direct Booking" or web
                $channel = \App\Models\Channel::where('name', 'like', '%Direct%')->first();
                $channelId = $channel ? $channel->id : 1;

                $bookingReference = strtoupper(uniqid('WEB-'));
                $bookings = [];

                // Generates a proper confirmation code like the admin side
                $confirmationCode = \Illuminate\Support\Str::random(10);

                // 2. Loop through each requested room and find a physical unit
                foreach ($validated['rooms'] as $room) {
                    $checkIn = $validated['check_in_date'];
                    $checkOut = $validated['check_out_date'];

                    // Find first available unit for this room type
                    $availableUnit = \App\Models\Unit::where('room_type_id', $room['room_type_id'])
                        ->whereNotIn('id', function ($query) use ($checkIn, $checkOut) {
                            $query->select('property_unit_id')
                                ->from('bookings')
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        })
                        ->first();

                    if (!$availableUnit) {
                        throw new \Exception("A room of type ID {$room['room_type_id']} is no longer available for these dates.");
                    }

                    // 3. Create the Booking Record matching the exact DB Schema
                    $booking = \App\Models\Booking::create([
                        'guest_id' => $guest->id,
                        'property_id' => $room['property_id'],
                        'room_type_id' => $room['room_type_id'],
                        'property_unit_id' => $availableUnit->id,
                        'hosting_company_id' => $hostingCompanyId,
                        'channel_id' => $channelId,
                        'check_in_date' => $checkIn,
                        'check_out_date' => $checkOut,
                        'number_of_guests' => $validated['guests'],
                        
                        // Pricing Fields (Calculate ADR)
                        'raw_room_rate' => $room['price'] / max($room['nights'], 1),
                        'room_rate_modifier' => $room['price'] / max($room['nights'], 1),
                        'total_amount' => $room['price'],
                        'amount_paid' => 0.00,
                        'amount_due' => $room['price'],
                        'deposit_not_collected' => 0,
                        
                        'status' => 1, // 1 = Confirmed
                        'confirmation_code' => $confirmationCode,
                        'remarks' => 'Direct Guest Web Booking - ' . $bookingReference
                    ]);

                    // 4. Record the Payment
                    $booking->payments()->create([
                        'amount' => 0.00,
                        'payment_method' => 'Pay at Property', // Default demo method
                        'payment_gateway' => 'System',
                        'type' => 1,
                        'transaction_id' => \Illuminate\Support\Str::random(20),
                        'status' => 0, // 0 = Pending
                    ]);

                    $bookings[] = $booking;
                }

                return [
                    'bookings' => $bookings,
                    'reference' => $bookingReference
                ];
            });

            // Dispatch Confirmation Email for the first booking (usually only one per checkout transaction)
            if (!empty($result['bookings'])) {
                try {
                    $mainBooking = $result['bookings'][0]->load(['guest', 'property']);
                    Mail::to($mainBooking->guest->email)->send(new BookingConfirmationMail($mainBooking));
                } catch (\Exception $e) {
                    \Log::error("Booking Confirmation Email Error: " . $e->getMessage());
                    // Don't fail the whole request if email fails, but log it
                }
            }

            return response()->json([
                'message' => 'Booking created successfully',
                'booking_reference' => $result['reference'],
                'guest_portal_token' => $result['bookings'][0]->guest_portal_token ?? null,
            ], 201);

        } catch (\Exception $e) {
            \Log::error("Guest Booking Error: " . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'message' => 'Failed to complete booking. One or more rooms may have been taken.'
            ], 409);
        }
    }

    /**
     * Check real-time availability for a property and date range
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'nullable|integer|min:1',
        ]);

        $propertyId = $validated['property_id'];
        $checkIn = $validated['check_in'];
        $checkOut = $validated['check_out'];
        $guests = $validated['guests'] ?? 1;

        // Fetch room types that can accommodate the guests
        $roomTypes = \App\Models\RoomType::where('property_id', $propertyId)
            ->where(function ($query) use ($guests) {
                if ($guests > 0) {
                    $query->whereRaw('(IFNULL(max_adults, 0) + IFNULL(max_children, 0)) >= ?', [$guests]);
                }
            })
            ->with(['units' => function ($uQuery) use ($checkIn, $checkOut) {
                // Only load units that are NOT booked for these dates
                $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                    $subQuery->select('property_unit_id')
                        ->from('bookings')
                        ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                        ->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn)
                        ->whereNotNull('property_unit_id');
                });
            }])
            ->get();

        $availability = $roomTypes->map(function ($rt) {
            return [
                'room_type_id' => $rt->id,
                'available_units' => $rt->units->count(),
                'is_available' => $rt->units->count() > 0,
            ];
        });

        // Determine if ANY room is available
        $hasAnyAvailability = $availability->contains('is_available', true);

        return response()->json([
            'is_available' => $hasAnyAvailability,
            'room_types' => $availability
        ]);
    }
}
