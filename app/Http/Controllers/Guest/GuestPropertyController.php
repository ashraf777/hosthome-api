<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class GuestPropertyController extends Controller
{
    /**
     * Display a listing of public active properties.
     */
    public function index(Request $request)
    {
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $guests = (int) $request->input('guests', 1);

        $query = Property::with([
            'photos',
            'roomTypes' => function ($query) use ($checkIn, $checkOut, $guests) {
                if ($guests > 0) {
                    $query->whereRaw('(IFNULL(max_adults, 0) + IFNULL(max_children, 0)) >= ?', [$guests]);
                }

                if ($checkIn && $checkOut) {
                    $query->whereHas('units', function ($uQuery) use ($checkIn, $checkOut) {
                        $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                            $subQuery->select('property_unit_id')
                                ->from('bookings')
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        });
                    });
                }

                $query->with(['photos', 'units' => function($uQuery) use ($checkIn, $checkOut) {
                    if ($checkIn && $checkOut) {
                        $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                            $subQuery->select('property_unit_id')
                                ->from('bookings')
                                // User mentioned status 1 is confirmed
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        });
                    }
                }]);
            }, 
            'amenities.amenityReference'
        ])->where('status', 1)
          ->where('listing_status', 'active');
          
        // 1. Location Search
        if ($request->has('location') && !empty($request->input('location'))) {
            $location = $request->input('location');
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                  ->orWhere('state', 'like', "%{$location}%")
                  ->orWhere('country', 'like', "%{$location}%")
                  ->orWhere('name', 'like', "%{$location}%");
            });
        }
        
        // 2 & 3. Guests and Availability Search
        if ($guests > 0 || ($checkIn && $checkOut)) {
            $query->whereHas('roomTypes', function ($rtQuery) use ($guests, $checkIn, $checkOut) {
                // Guests Filter (max_adults + max_children >= requested guests)
                if ($guests > 0) {
                    $rtQuery->whereRaw('(IFNULL(max_adults, 0) + IFNULL(max_children, 0)) >= ?', [$guests]);
                }

                // Availability Filter
                if ($checkIn && $checkOut) {
                    $rtQuery->whereHas('units', function ($uQuery) use ($checkIn, $checkOut) {
                        $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                            $subQuery->select('property_unit_id')
                                ->from('bookings')
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        });
                    });
                }
            });
        }
        
        $properties = $query->paginate(15);
        
        // We could use a Resource to hide sensitive fields, but for now just returning JSON
        return response()->json($properties);
    }

    /**
     * Display the specified public property.
     */
    public function show(Request $request, $id)
    {
        $checkIn = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $property = Property::with([
            'photos',
            'roomTypes' => function ($query) use ($checkIn, $checkOut) {
                if ($checkIn && $checkOut) {
                    $query->whereHas('units', function ($uQuery) use ($checkIn, $checkOut) {
                        $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                            $subQuery->select('property_unit_id')
                                ->from('bookings')
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        });
                    });
                }

                $query->with(['photos', 'amenities.amenityReference', 'units' => function($uQuery) use ($checkIn, $checkOut) {
                    if ($checkIn && $checkOut) {
                        $uQuery->whereNotIn('id', function ($subQuery) use ($checkIn, $checkOut) {
                            $subQuery->select('property_unit_id')
                                ->from('bookings')
                                ->whereIn('status', [1, '1', 'Confirmed', 'confirmed'])
                                ->where('check_in_date', '<', $checkOut)
                                ->where('check_out_date', '>', $checkIn)
                                ->whereNotNull('property_unit_id');
                        });
                    }
                }]);
            }, 
            'amenities.amenityReference',
        ])->where('status', 1) // Ensure it is an active property
          ->where('listing_status', 'active')
          ->findOrFail($id);
          
        return response()->json($property);
    }
}
