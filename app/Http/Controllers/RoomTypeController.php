<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Property;
use App\Http\Resources\RoomTypeResource;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if (!$request->user()->canPermission('room-type:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $query = RoomType::query()->where('hosting_company_id', $request->user()->hosting_company_id)
                         ->with(['amenities', 'photos', 'properties', 'property']);

        // Filter room types by a specific property
        // if ($request->has('property_id')) {
        //     $query->whereHas('properties', function ($q) use ($request) {
        //         $q->where('property_id', $request->input('property_id'));
        //     });
        // }

        return RoomTypeResource::collection($query->paginate());
    }

    /**
     * Store a new room type for the hosting company.
     */
    public function store(Request $request)
    {
        // if (!$request->user()->canPermission('room-type:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_adults' => 'nullable|integer|min:0',
            'max_children' => 'nullable|integer|min:0',
            'size' => 'nullable|string|max:20',
            'weekday_price' => 'nullable|numeric|min:0',
            'weekend_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
            'property_id' => 'nullable|integer',
            'property_ids' => 'nullable|array',
            'property_ids.*' => 'integer|exists:properties,id',
        ]);

        $validated['hosting_company_id'] = $request->user()->hosting_company_id;

        $roomType = RoomType::create($validated);

        // If property_ids are provided, attach the new room type to them
        // if (!empty($validated['property_ids'])) {
        //     // Security Check: Ensure the properties belong to the same hosting company
        //     $properties = Property::where('hosting_company_id', $request->user()->hosting_company_id)
        //                           ->whereIn('id', $validated['property_ids'])->get();
            
        //     if (count($properties) === count($validated['property_ids'])) {
        //         $roomType->properties()->attach($validated['property_ids']);
        //     }
        // }

        $roomType->load(['amenities', 'photos', 'properties', 'property']);

        return new RoomTypeResource($roomType);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, RoomType $roomType)
    {
        // Tenancy Check
        if ($roomType->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('room-type:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }
        
        $roomType->load(['amenities', 'photos', 'properties', 'property']);

        return new RoomTypeResource($roomType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RoomType $roomType)
    {
        // Tenancy Check
        if ($roomType->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('room-type:update')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'max_adults' => 'sometimes|nullable|integer|min:0',
            'max_children' => 'sometimes|nullable|integer|min:0',
            'size' => 'sometimes|nullable|string|max:20',
            'weekday_price' => 'sometimes|nullable|numeric|min:0',
            'weekend_price' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|nullable|boolean',
        ]);

        $roomType->update($validated);
        $roomType->load(['amenities', 'photos', 'properties', 'property']);

        return new RoomTypeResource($roomType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, RoomType $roomType)
    {
        // Tenancy Check
        if ($roomType->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (!$request->user()->canPermission('room-type:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $roomType->delete();

        return response()->noContent();
    }

    /**
     * Assign a room type to a specific property.
     */
    public function assignToProperty(Request $request, Property $property, RoomType $roomType)
    {
        // Tenancy Check
        // if ($property->hosting_company_id !== $request->user()->hosting_company_id || $roomType->hosting_company_id !== $request->user()->hosting_company_id) {
        //     return response()->json(['message' => 'Invalid property or room type specified.'], 422);
        // }

        $property->roomTypes()->syncWithoutDetaching($roomType->id);

        return response()->json(['message' => 'Room type assigned successfully.']);
    }

    /**
     * Remove a room type from a specific property.
     */
    public function removeFromProperty(Request $request, Property $property, RoomType $roomType)
    {
        // Tenancy Check
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Invalid property specified.'], 422);
        }

        $property->roomTypes()->detach($roomType->id);

        return response()->json(['message' => 'Room type removed successfully.']);
    }

    public function indexByProperty(Request $request, Property $property)
    {
        // Tenancy Check: Ensure the property belongs to the user's company.
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // if (!$request->user()->canPermission('room-type:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        // Retrieve room types using the hasMany relationship from the Property model
        $roomTypes = RoomType::where('property_id', $property->id)
                               ->with(['amenities', 'photos'])
                               ->get();

        return RoomTypeResource::collection($roomTypes);
    }
}
