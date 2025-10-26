<?php

namespace App\Http\Controllers;

use App\Models\PropertyOwner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyOwnerController extends Controller
{
    // NOTE: This controller assumes tenant database connection switching happens externally 
    // or is handled by a model service provider.
    
    /**
     * GET /api/property-owners
     */
    public function index(Request $request)
    {
        // No explicit tenant scoping needed here, as the DB connection is already scoped.
        $owners = PropertyOwner::with('properties')->paginate(15);
        
        return JsonResource::collection($owners);
    }

    /**
     * POST /api/property-owners
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:property_owners,email',
            'status' => 'nullable|integer',
        ]);
        
        $owner = PropertyOwner::create($validated);
        
        return new JsonResource($owner);
    }

    /**
     * GET /api/property-owners/{propertyOwner}
     */
    public function show(PropertyOwner $propertyOwner)
    {
        return new JsonResource($propertyOwner->load('properties'));
    }

    /**
     * PUT/PATCH /api/property-owners/{propertyOwner}
     */
    public function update(Request $request, PropertyOwner $propertyOwner)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:property_owners,email,' . $propertyOwner->id,
            'status' => 'nullable|integer',
        ]);
        
        $propertyOwner->update($validated);
        
        return new JsonResource($propertyOwner);
    }

    /**
     * DELETE /api/property-owners/{propertyOwner}
     */
    public function destroy(PropertyOwner $propertyOwner)
    {
        // Database FK constraint will prevent deletion if properties exist.
        $propertyOwner->delete();
        
        return response()->json(['message' => 'Property Owner deleted successfully.'], 200);
    }
}