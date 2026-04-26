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
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('amenity:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return AmenityResource::collection(Amenity::with('amenityReference')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->canPermission('amenity:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validatedData = $request->validate([
            'amenities_reference_id' => 'required|exists:amenities_references,id',
            'specific_name' => 'nullable|string|max:255',
            'status' => 'required|integer',
            'type' => 'nullable|integer|in:1,2,3',
        ]);

        $amenity = Amenity::create($validatedData);
        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Amenity $amenity)
    {
        if (!$request->user()->canPermission('amenity:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Amenity $amenity)
    {
        if (!$request->user()->canPermission('amenity:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validatedData = $request->validate([
            'amenities_reference_id' => 'sometimes|required|exists:amenities_references,id',
            'specific_name' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|required|integer',
            'type' => 'sometimes|nullable|integer|in:1,2,3',
        ]);

        $amenity->update($validatedData);
        $amenity->load('amenityReference');
        return new AmenityResource($amenity);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Amenity $amenity)
    {
        if (!$request->user()->canPermission('amenity:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $amenity->delete();
        return response()->noContent();
    }
}
