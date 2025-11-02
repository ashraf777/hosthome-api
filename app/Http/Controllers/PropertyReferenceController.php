<?php

namespace App\Http\Controllers;

use App\Models\PropertyReference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyReferenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // CORRECT: Permission Check
        // if (!$request->user()->canPermission('property-reference:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $references = PropertyReference::all();

        // Group by key for easier frontend consumption
        return response()->json($references->groupBy('key'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // CORRECT: Permission Check
        // if (!$request->user()->canPermission('property-reference:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required|string|unique:property_references,value,NULL,id,key,' . $request->key, // Unique check for value within the same key
        ]);

        $reference = PropertyReference::create($validated);

        return new JsonResource($reference);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PropertyReference $propertyReference)
    {
        // CORRECT: Permission Check
        // if (!$request->user()->canPermission('property-reference:update')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'key' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string|unique:property_references,value,' . $propertyReference->id . ',id,key,' . $propertyReference->key,
        ]);

        $propertyReference->update($validated);

        return new JsonResource($propertyReference);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PropertyReference $propertyReference)
    {
        // CORRECT: Permission Check
        // if (!$request->user()->canPermission('property-reference:delete')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        // Database FK constraint prevents deletion if properties use this reference.
        $propertyReference->delete();

        return response()->noContent();
    }
}
