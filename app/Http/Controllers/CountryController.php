<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryController extends Controller
{
    /**
     * GET /api/countries
     * Retrieves a read-only list of all active countries (Lookup Data).
     */
    public function index()
    {
        $countries = Country::where('status', 1)->get();
        
        return JsonResource::collection($countries->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'iso_code' => $country->iso_code,
                'currency_code' => $country->currency_code,
            ];
        }));
    }
}
