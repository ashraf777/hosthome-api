<?php

namespace App\Http\Controllers;

use App\Models\PropertyOwner;
use Illuminate\Http\Request;
use App\Http\Resources\PropertyOwnerResource;

class PropertyOwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if (!$request->user()->canPermission('property-owner:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $owners = PropertyOwner::where('hosting_company_id', $request->user()->hosting_company_id)
            ->with('properties')
            ->latest()
            ->paginate(15);

        return PropertyOwnerResource::collection($owners);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // if (!$request->user()->canPermission('property-owner:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:property_owners,email',
            'status' => 'nullable|integer',
            'hosting_company_id' => 'required|integer|exists:hosting_companies,id',
        ]);

        $owner = PropertyOwner::create($validated);

        return new PropertyOwnerResource($owner);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PropertyOwner $propertyOwner)
    {
        if ($propertyOwner->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if (!$request->user()->canPermission('property-owner:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        return new PropertyOwnerResource($propertyOwner->load('properties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PropertyOwner $propertyOwner)
    {
        if ($propertyOwner->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if (!$request->user()->canPermission('property-owner:update')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:property_owners,email,' . $propertyOwner->id,
            'status' => 'nullable|integer',
        ]);

        $propertyOwner->update($validated);

        return new PropertyOwnerResource($propertyOwner);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PropertyOwner $propertyOwner)
    {
        if ($propertyOwner->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        if (!$request->user()->canPermission('property-owner:delete')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $propertyOwner->delete();

        return response()->noContent();
    }
}
