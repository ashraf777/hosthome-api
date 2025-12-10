<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Property;
use App\Models\Booking;
use App\Models\RoomType;
use Illuminate\Support\Facades\Validator;

class MultiCalendarController extends Controller
{
    /**
     * Handle the incoming request for the multi-calendar.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|integer|exists:properties,id',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'statuses' => 'nullable|string',
            'channels' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $propertyId = $request->input('property_id');
        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $statuses = $request->input('statuses') ? explode(',', $request->input('statuses')) : [];
        $channels = $request->input('channels') ? explode(',', $request->input('channels')) : [];

        $roomTypes = RoomType::with('units')->where('property_id', $propertyId)->get();

        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        // Fetch bookings and organize them in a map for efficient lookup.
        $bookingsQuery = Booking::with('bookingTypeReference')
            ->where('property_id', $propertyId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('check_in_date', '<=', $endDate->format('Y-m-d'))
                      ->where('check_out_date', '>', $startDate->format('Y-m-d'));
            });

        if (!empty($statuses)) {
            $bookingsQuery->whereIn('status', $statuses);
        }

        if (!empty($channels)) {
            $bookingsQuery->whereIn('channel_id', $channels);
        }
        
        $bookings = $bookingsQuery->get();

        // Map bookings by unit_id and date for O(1) access.
        $bookingsMap = [];
        foreach ($bookings as $booking) {
            $checkIn = Carbon::parse($booking->check_in_date);
            $checkOut = Carbon::parse($booking->check_out_date);
            $bookingPeriod = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());

            foreach ($bookingPeriod as $bookingDate) {
                $dateStr = $bookingDate->format('Y-m-d');
                // Only map dates that are within the requested date range
                if ($bookingDate->between($startDate, $endDate)) {
                    $bookingsMap[$booking->property_unit_id][$dateStr] = $booking;
                }
            }
        }

        $response = [];
        foreach ($roomTypes as $roomType) {
            $roomTypeData = [
                'id' => 'room-type-' . $roomType->id,
                'name' => $roomType->name,
                'dates' => [],
                'units' => [],
            ];

            // Initialize dates for the room type with default inventory and empty rates.
            foreach ($dates as $date) {
                $roomTypeData['dates'][$date] = [
                    'inventory' => $roomType->units->count() . '|' . $roomType->units->count(),
                    'rates' => $roomType->weekday_price ? (object)['default' => 'MYR ' . number_format($roomType->weekday_price, 2)] : ''
                ];
            }

            foreach ($roomType->units as $unit) {
                $unitData = [
                    'id' => 'unit-' . $unit->id,
                    'name' => $unit->unit_identifier,
                    'dates' => [],
                ];

                foreach ($dates as $date) {
                    if (isset($bookingsMap[$unit->id][$date])) {
                        $booking = $bookingsMap[$unit->id][$date];
                        $unitData['dates'][$date]['status'] = optional($booking->bookingTypeReference)->name ?? 'Confirm Booking';
                        $unitData['dates'][$date]['booking'] = $booking;

                        // Add rate to the parent room type for this date.
                        if (isset($booking->total_amount)) {
                            $rateKey = 'rate-' . $booking->id;
                            $roomTypeData['dates'][$date]['rates']->$rateKey = 'MYR ' . number_format($booking->total_amount, 2);
                        }
                    } else {
                        $unitData['dates'][$date] = ['status' => ''];
                    }
                }
                $roomTypeData['units'][] = $unitData;
            }
            
            // Recalculate inventory based on bookings.
            foreach ($dates as $date) {
                $bookedCount = 0;
                foreach ($roomTypeData['units'] as $unitData) {
                    if (isset($unitData['dates'][$date]['booking'])) {
                        $bookedCount++;
                    }
                }
                $totalUnits = $roomType->units->count();
                $availableCount = $totalUnits - $bookedCount;
                $roomTypeData['dates'][$date]['inventory'] = $availableCount . '|' . $totalUnits;
            }

            $response[] = $roomTypeData;
        }

        return response()->json($response);
    }
}
