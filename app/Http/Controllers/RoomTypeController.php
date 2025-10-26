<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request; // Using generic Request for demo brevity
use Illuminate\Http\JsonResponse;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $roomTypes = RoomType::with(['property', 'amenities', 'units'])->latest()->paginate(15);

        return response()->json([
            'code' => 200,
            'message' => 'Room Types retrieved successfully.',
            'data' => $roomTypes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // **NOTE:** Use a dedicated StoreRoomTypeRequest for full validation.
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'name' => 'required|string|max:255',
            'weekday_price' => 'required|numeric|min:0',
            'weekend_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0', // New field
            'amenities_ids' => 'array', // Assuming you pass amenity IDs to attach
            // ... all other fields
        ]);
        
        $roomType = RoomType::create($validated);
        
        // Handle many-to-many relationship (Amenities)
        if (isset($validated['amenities_ids'])) {
            $roomType->amenities()->attach($validated['amenities_ids']);
        }

        return response()->json([
            'code' => 201,
            'message' => 'Room Type created successfully.',
            'data' => $roomType
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RoomType $roomType): JsonResponse
    {
        $roomType->load(['property', 'units', 'amenities', 'photos', 'bedArrangements']);

        return response()->json([
            'code' => 200,
            'message' => 'Room Type retrieved successfully.',
            'data' => $roomType
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RoomType $roomType): JsonResponse
    {
        // **NOTE:** Use a dedicated UpdateRoomTypeRequest for full validation.
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'weekday_price' => 'sometimes|numeric|min:0',
            'amenities_ids' => 'sometimes|array',
            'sale_price' => 'nullable|numeric|min:0', // New field
            // ... all other fields
        ]);
        
        $roomType->update($validated);

        // Sync many-to-many relationship (Amenities)
        if (isset($validated['amenities_ids'])) {
            $roomType->amenities()->sync($validated['amenities_ids']);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Room Type updated successfully.',
            'data' => $roomType
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoomType $roomType): JsonResponse
    {
        $roomType->delete();

        return response()->json([
            'code' => 204,
            'message' => 'Room Type deleted successfully.'
        ], 204);
    }
}