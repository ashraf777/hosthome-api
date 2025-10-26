<?php

namespace App\Http\Controllers;

use App\Models\PropertyReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReferenceController extends Controller
{
    /**
     * GET /api/property-references
     * Lists all global reference keys for properties.
     */
    public function index()
    {
        $references = PropertyReference::all();
        
        // Group by key for easier frontend consumption
        return response()->json($references->groupBy('key'));
    }

    /**
     * POST /api/property-references
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string|unique:property_references,value,NULL,id,key,' . $request->key, // Unique check for value within the same key
        ]);
        
        $reference = PropertyReference::create($validated);
        
        return new JsonResource($reference);
    }

    /**
     * PUT/PATCH /api/property-references/{propertyReference}
     */
    public function update(Request $request, PropertyReference $propertyReference)
    {
        $validated = $request->validate([
            'key' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string|unique:property_references,value,' . $propertyReference->id . ',id,key,' . $propertyReference->key,
        ]);
        
        $propertyReference->update($validated);
        
        return new JsonResource($propertyReference);
    }

    /**
     * DELETE /api/property-references/{propertyReference}
     */
    public function destroy(PropertyReference $propertyReference)
    {
        // Database FK constraint prevents deletion if properties use this reference.
        $propertyReference->delete();
        
        return response()->json(['message' => 'Property Reference deleted successfully.'], 200);
    }
}