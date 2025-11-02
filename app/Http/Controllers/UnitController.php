<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Http\Resources\UnitResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if (!$request->user()->canPermission('unit:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $hostingCompanyId = $request->user()->hosting_company_id;

        // Tenancy check is now done directly on the property
        $units = Unit::whereHas('property', function ($query) use ($hostingCompanyId) {
            $query->where('hosting_company_id', $hostingCompanyId);
        })->with(['property', 'roomType', 'unitTypeRef', 'owner'])->paginate();

        return UnitResource::collection($units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // if (!$request->user() || !$request->user()->canPermission('unit:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $hostingCompanyId = $request->user()->hosting_company_id;

        $validated = $request->validate([
            'property_id' => [ // <-- ADDED
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(function ($query) use ($hostingCompanyId) {
                    $query->where('hosting_company_id', $hostingCompanyId);
                }),
            ],
            'room_type_id' => [
                'required',
                'integer',
                // Optional: You might want to add a rule to ensure the room_type_id 
                // belongs to the provided property_id for data integrity.
                Rule::exists('room_types', 'id'),
            ],
            'unit_type_ref_id' => 'nullable|integer|exists:property_unit_references,id',
            'unit_identifier' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['available', 'maintenance', 'owner_use'])],
            'description' => 'nullable|string',
            'about' => 'nullable|string',
            'guest_access' => 'nullable|string',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'max_free_stay_days' => 'nullable|integer|min:0',
        ]);

        $unit = Unit::create($validated);
        $unit->load(['property', 'roomType', 'unitTypeRef', 'owner']);

        return new UnitResource($unit);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Unit $unit)
    {
        // Tenancy Check is now based on the direct property relationship
        if ($unit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('unit:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $unit->load(['property', 'roomType', 'unitTypeRef', 'owner']);

        return new UnitResource($unit);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        // Tenancy Check
        if ($unit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('unit:update')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $hostingCompanyId = $request->user()->hosting_company_id;

        $validated = $request->validate([
            // Do not allow changing the property_id after creation to maintain integrity
            'room_type_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('room_types', 'id'),
            ],
            'unit_type_ref_id' => 'sometimes|nullable|integer|exists:property_unit_references,id',
            'unit_identifier' => 'sometimes|nullable|string|max:255',
            'status' => ['sometimes', 'required', Rule::in(['available', 'maintenance', 'owner_use'])],
            'description' => 'sometimes|nullable|string',
            'about' => 'sometimes|nullable|string',
            'guest_access' => 'sometimes|nullable|string',
            'owner_user_id' => 'sometimes|nullable|integer|exists:users,id',
            'max_free_stay_days' => 'sometimes|nullable|integer|min:0',
        ]);

        $unit->update($validated);
        $unit->load(['property', 'roomType', 'unitTypeRef', 'owner']);

        return new UnitResource($unit);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Unit $unit)
    {
        // Tenancy Check
        if ($unit->property->hosting_company_id !== $request->user()->hosting_company_id) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        // if (!$request->user()->canPermission('unit:delete')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $unit->delete();

        return response()->noContent();
    }
}
