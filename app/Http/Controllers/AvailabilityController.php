<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function getAvailability(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // CORRECT: Find property and perform tenancy check in a single, secure query.
        // This will throw a 404 if the property doesn't exist or doesn't belong to the user's company.
        $property = Property::where('id', $validated['property_id'])
                            ->where('hosting_company_id', $request->user()->hosting_company_id)
                            ->firstOrFail();

        // CORRECT: Use the application's canPermission() method for authorization.
        if (!$request->user()->canPermission('property:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // Get all units associated with the property
        $propertyUnits = PropertyUnit::whereHas('roomType', function ($query) use ($property) {
            $query->where('property_id', $property->id);
        })->get();

        $unitIds = $propertyUnits->pluck('id');
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Fetch all relevant bookings in a single query
        $bookings = Booking::whereIn('property_unit_id', $unitIds)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('check_in_date', '<=', $endDate)
                      ->where('check_out_date', '>', $startDate);
            })
            ->get();

        // Create a lookup map for booked dates
        $bookingMap = [];
        foreach ($bookings as $booking) {
            $runnerDate = Carbon::parse($booking->check_in_date);
            $checkoutDate = Carbon::parse($booking->check_out_date);
            while ($runnerDate->lt($checkoutDate) && $runnerDate->lte($endDate)) {
                if ($runnerDate->gte($startDate)) {
                    $bookingMap[$booking->property_unit_id][$runnerDate->toDateString()] = true;
                }
                $runnerDate->addDay();
            }
        }

        // Build the availability response
        $availability = [];
        $currentDate = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);

        while ($currentDate->lte($endDateCarbon)) {
            $dateString = $currentDate->toDateString();
            $availability[$dateString] = [];

            foreach ($propertyUnits as $unit) {
                $isBooked = isset($bookingMap[$unit->id][$dateString]);
                $availability[$dateString][$unit->id] = $isBooked ? 'booked' : 'available';
            }

            $currentDate->addDay();
        }

        return response()->json($availability);
    }
}
