<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityReferenceResource;
use App\Models\AmenityReference;
use Illuminate\Http\Request;

class AmenityReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return AmenityReferenceResource::collection(AmenityReference::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:amenities_references,name',
            'type' => 'nullable|integer|in:1,2,3',
        ]);

        $amenityReference = AmenityReference::create($validatedData);
        return new AmenityReferenceResource($amenityReference);
    }

    /**
     * Display the specified resource.
     */
    public function show(AmenityReference $amenityReference)
    {
        return new AmenityReferenceResource($amenityReference);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AmenityReference $amenityReference)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:amenities_references,name,' . $amenityReference->id,
            'type' => 'sometimes|nullable|integer|in:1,2,3',
        ]);

        $amenityReference->update($validatedData);
        return new AmenityReferenceResource($amenityReference);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AmenityReference $amenityReference)
    {
        $amenityReference->delete();
        return response()->noContent();
    }
}
