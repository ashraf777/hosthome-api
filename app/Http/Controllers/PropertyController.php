<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyController extends Controller
{
    /**
     * GET /api/properties
     */
    public function index()
    {
        $properties = Property::with(['owner', 'typeReference'])->paginate(15);
        
        return JsonResource::collection($properties);
    }

    /**
     * POST /api/properties
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_owner_id' => 'required|exists:property_owners,id',
            'property_type_ref_id' => 'nullable|exists:property_references,id',
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'timezone' => 'required|string|max:50',
            'listing_status' => 'nullable|in:draft,active,archived',
            'status' => 'nullable|integer',
        ]);
        
        $property = Property::create($validated);
        
        return new JsonResource($property);
    }

    /**
     * GET /api/properties/{property}
     */
    public function show(Property $property)
    {
        return new JsonResource($property->load(['owner', 'typeReference', 'roomTypes']));
    }

    /**
     * PUT/PATCH /api/properties/{property}
     */
    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'property_owner_id' => 'sometimes|required|exists:property_owners,id',
            'property_type_ref_id' => 'nullable|exists:property_references,id',
            'name' => 'sometimes|required|string|max:255',
            'address_line_1' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'zip_code' => 'sometimes|required|string|max:20',
            'timezone' => 'sometimes|required|string|max:50',
            'listing_status' => 'sometimes|required|in:draft,active,archived',
            'status' => 'nullable|integer',
        ]);
        
        $property->update($validated);
        
        return new JsonResource($property);
    }

    /**
     * DELETE /api/properties/{property}
     */
    public function destroy(Property $property)
    {
        // Database FK constraint prevents deletion if room_types exist.
        $property->delete();
        
        return response()->json(['message' => 'Property deleted successfully.'], 200);
    }
}