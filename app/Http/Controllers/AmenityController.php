<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return AmenityResource::collection(Amenity::with('amenityReference')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'amenities_reference_id' => 'required|exists:amenities_references,id',
            'specific_name' => 'required|string|max:255',
            'status' => 'required|integer',
        ]);

        $amenity = Amenity::create($validatedData);
        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Display the specified resource.
     */
    public function show(Amenity $amenity)
    {
        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Amenity $amenity)
    {
        $validatedData = $request->validate([
            'amenities_reference_id' => 'sometimes|required|exists:amenities_references,id',
            'specific_name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|integer',
        ]);

        $amenity->update($validatedData);
        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Amenity $amenity)
    {
        $amenity->delete();
        return response()->noContent();
    }
}
