<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request; // Using generic Request for demo brevity
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $units = Unit::with(['roomType.property', 'owner', 'fixedCosts'])->latest()->paginate(15);

        return response()->json([
            'code' => 200,
            'message' => 'Units retrieved successfully.',
            'data' => $units
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // **NOTE:** Use a dedicated StoreUnitRequest for full validation.
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'name' => 'required|string|max:255|unique:units,name',
            'owner_user_id' => 'nullable|exists:users,id',
            'max_free_stay_days' => 'required|integer|min:0',
            'fine_print' => 'nullable|string',
            // ... other fields and nested costs/photos to handle
        ]);

        $unit = Unit::create($validated);

        // You would typically handle nested resource creation (like fixed costs) here
        // Example: $unit->fixedCosts()->createMany($request->fixed_costs);

        return response()->json([
            'code' => 201,
            'message' => 'Unit created successfully.',
            'data' => $unit
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit): JsonResponse
    {
        $unit->load(['roomType.property', 'owner', 'parentUnit', 'fixedCosts']);

        return response()->json([
            'code' => 200,
            'message' => 'Unit retrieved successfully.',
            'data' => $unit
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        // **NOTE:** Use a dedicated UpdateUnitRequest for full validation.
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:units,name,' . $unit->id,
            // ... other fields
        ]);
        
        $unit->update($validated);

        // Example for updating nested fixed costs:
        // You would use updateOrCreate or sync logic here for fixed costs.

        return response()->json([
            'code' => 200,
            'message' => 'Unit updated successfully.',
            'data' => $unit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();

        return response()->json([
            'code' => 204,
            'message' => 'Unit deleted successfully.'
        ], 204);
    }
}