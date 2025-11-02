<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyAmenityController extends Controller
{
    /**
     * Sync the amenities for a given property.
     * Replaces all existing amenities with the new set of amenities.
     */
    public function store(Request $request, Property $property)
    {
        // Tenancy check to ensure the property belongs to the user's hosting company
        if ($property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'amenity_ids' => 'present|array',
        ]);

        // The sync method handles attaching and detaching in one go.
        $property->amenities()->sync($validated['amenity_ids']);

        return response()->json(['message' => 'Amenities updated successfully.']);
    }
}
