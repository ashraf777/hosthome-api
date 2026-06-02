<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('booking:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $user = $request->user();
        $query = Booking::with(['guest', 'property', 'roomType', 'propertyUnit', 'hostingCompany', 'bookingTypeReference', 'channelReference']);
        
        if ($user && $user->hosting_company_id) {
            $query->where('hosting_company_id', $user->hosting_company_id);
        }

        return BookingResource::collection($query->orderBy('created_at', 'desc')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->canPermission('booking:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'guest.first_name' => 'required|string|max:255',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date',
            'total_amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $unitId = $request->input('property_unit_id');
        $checkIn = $request->input('check_in_date');
        $checkOut = $request->input('check_out_date');

        $conflictExists = Booking::where('property_unit_id', $unitId)
            // Check for overlap: Start date is before the new end, AND End date is after the new start
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->exists();

        if ($conflictExists) {
            return response()->json(['error' => 'The selected unit is unavailable for these dates.'], 409);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                $guestData = $request->input('guest');
                $guest = Guest::updateOrCreate(
                    ['email' => $guestData['email']],
                    $guestData
                );

                if ($request->has('vehicles')) {
                    $guest->vehicles()->delete();
                    foreach ($request->input('vehicles') as $vehicle) {
                        $guest->vehicles()->create([
                            'registration_number' => $vehicle['registration_number']
                        ]);
                    }
                }
                
                $bookingData = $request->except(['guest', 'vehicles', 'items_provided', 'charges', 'payment']);
                
                if (empty($bookingData['confirmation_code'])) {
                    $bookingData['confirmation_code'] = Str::random(10); 
                }

                $booking = Booking::create(array_merge($bookingData, ['guest_id' => $guest->id]));

                if ($request->has('items_provided')) {
                    foreach ($request->input('items_provided') as $item) {
                        $booking->itemsProvided()->create(['item_name' => $item['name']]);
                    }
                }

                if ($request->has('charges')) {
                    foreach ($request->input('charges') as $charge) {
                        $booking->charges()->create([
                            'charge_reference_id' => $charge['charge_reference_id'],
                            'amount' => $charge['amount'],
                        ]);
                    }
                }

                if ($request->has('payment')) {
                    $paymentData = $request->input('payment');
                    
                    // Generate a unique transaction ID and merge it into the payment data
                    $paymentData['transaction_id'] = Str::random(20); 

                    $booking->payments()->create($paymentData);
                }
                
                return $booking->load(['guest.vehicles', 'itemsProvided', 'charges', 'payments']);
            });

            // Dispatch push notification to admins of this hosting company
            try {
                $adminIds = \App\Models\User::where('hosting_company_id', $result->hosting_company_id)
                    ->whereHas('role', function ($q) {
                        $q->where('name', '!=', 'Staff/Cleaner');
                    })
                    ->pluck('id')
                    ->toArray();

                if (!empty($adminIds)) {
                    $guestName = $result->guest ? ($result->guest->first_name . ' ' . ($result->guest->last_name ?? '')) : 'Guest';
                    \App\Jobs\SendPushNotification::dispatch(
                        $adminIds,
                        'New Booking Created',
                        "Booking for {$guestName} from " . date('M d', strtotime($result->check_in_date)) . " to " . date('M d', strtotime($result->check_out_date)),
                        'booking',
                        $result->id
                    );
                }
            } catch (\Exception $ex) {
                \Log::error("Failed dispatching new booking notification: " . $ex->getMessage());
            }

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the booking.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Booking $booking)
    {
        if (!$request->user()->canPermission('booking:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return BookingResource::make($booking->load(['guest', 'property', 'roomType', 'propertyUnit', 'hostingCompany', 'bookingTypeReference', 'channelReference', 'guest.vehicles', 'itemsProvided', 'charges', 'payments']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        if (!$request->user()->canPermission('booking:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'guest.first_name' => 'sometimes|string|max:255',
            'check_in_date' => 'sometimes|date',
            'check_out_date' => 'sometimes|date',
            'total_amount' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $unitId = $request->input('property_unit_id', $booking->property_unit_id); // Use existing if not provided
        $checkIn = $request->input('check_in_date', $booking->check_in_date);
        $checkOut = $request->input('check_out_date', $booking->check_out_date);

        $conflictExists = Booking::where('property_unit_id', $unitId)
            ->where('id', '!=', $booking->id) // CRITICAL: Exclude the current booking
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->exists();

        if ($conflictExists) {
            return response()->json(['error' => 'Updating this booking creates a date conflict with another reservation.'], 409);
        }

        try {
            $result = DB::transaction(function () use ($request, $booking) {
                
                // 1. UPDATE BOOKING BASE DATA
                $booking->update($request->except(['guest', 'vehicles', 'items_provided', 'charges', 'payment']));

                // --- GUEST LOGIC: Maintain consistency with 'store' using email for upsert ---
                if ($request->has('guest') && $request->has('guest.email')) {
                    $guestData = $request->input('guest');
                    
                    // Find guest by email or update the existing one
                    $guest = Guest::updateOrCreate(
                        ['email' => $guestData['email']],
                        $guestData
                    );

                    // Update booking to link to the correct guest ID
                    if ($booking->guest_id !== $guest->id) {
                        $booking->guest_id = $guest->id;
                        $booking->save(); 
                    }
                } else if ($request->has('guest')) {
                    // If guest data but NO email, just update the currently linked guest's other fields
                    $booking->guest->update($request->input('guest'));
                    $guest = $booking->guest; 
                } else {
                    // Fallback to the current guest instance
                    $guest = $booking->guest;
                }
                // --------------------------------------------------------------------------------

                // 2. VEHICLES LOGIC (Use the determined $guest instance)
                if ($request->has('vehicles')) {
                    // Use the correct $guest instance
                    $guest->vehicles()->delete();
                    foreach ($request->input('vehicles') as $vehicle) {
                        $guest->vehicles()->create([
                            'registration_number' => $vehicle['registration_number']
                        ]);
                    }
                }

                // 3. ITEMS PROVIDED LOGIC
                if ($request->has('items_provided')) {
                    $booking->itemsProvided()->delete();
                    foreach ($request->input('items_provided') as $item) {
                        $booking->itemsProvided()->create(['item_name' => $item['name']]);
                    }
                }

                // 4. CHARGES LOGIC
                if ($request->has('charges')) {
                    $booking->charges()->delete();
                    foreach ($request->input('charges') as $charge) {
                        $booking->charges()->create([
                            'charge_reference_id' => $charge['charge_reference_id'],
                            'amount' => $charge['amount'],
                        ]);
                    }
                }

                // 5. PAYMENT LOGIC (Consistent transaction ID generation)
                if ($request->has('payment')) {
                    $paymentData = $request->input('payment');
                    
                    // Generate a unique transaction ID 
                    $paymentData['transaction_id'] = Str::random(20); 

                    $booking->payments()->create($paymentData);
                }
                
                // Load the updated relationships
                return $booking->fresh()->load(['guest.vehicles', 'itemsProvided', 'charges.chargeReference', 'payments']);
            });

            return response()->json($result, 200);

        } catch (\Exception $e) {
            // Log the error for better debugging
            \Log::error("Booking Update Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            return response()->json(['message' => 'An error occurred while updating the booking.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Booking $booking)
    {
        if (!$request->user()->canPermission('booking:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $booking->delete();
        return response()->noContent();
    }
}
