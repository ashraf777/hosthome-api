<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Amenity;
use App\Models\Country;

class GuestLookupController extends Controller
{
    /**
     * Provide a list of active amenities for search filters
     */
    public function amenities(Request $request)
    {
        $amenities = Amenity::where('status', 1)->get();
        return response()->json($amenities);
    }

    /**
     * Provide a list of active countries for property filters or checkout form
     */
    public function countries(Request $request)
    {
        $countries = Country::where('status', 1)->get();
        return response()->json($countries);
    }
}
