<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeAmenityController extends Controller
{
    /**
     * Sync the amenities for a given room type.
     * Replaces all existing amenities with the new set of amenities.
     */
    public function store(Request $request, RoomType $roomType)
    {
        // Tenancy check to ensure the room type belongs to the user's hosting company
        // if ($roomType->property->hosting_company_id !== $request->user()->hosting_company_id) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $validated = $request->validate([
            'amenity_ids' => 'present|array',
            'amenity_ids.*' => 'exists:amenities,id',
        ]);

        $syncData = [];
        foreach ($validated['amenity_ids'] as $amenityId) {
            $syncData[$amenityId] = ['hosting_company_id' => $request->user()->hosting_company_id];
        }

        // The sync method handles attaching and detaching in one go.
        $roomType->amenities()->sync($syncData);

        return response()->json(['message' => 'Amenities updated successfully.']);
    }
}
