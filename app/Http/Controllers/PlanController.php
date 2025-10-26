<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanController extends Controller
{
    /**
     * GET /api/plans
     * Lists all subscription plans.
     */
    public function index()
    {
        $plans = Plan::orderBy('price_monthly')->get();
        return JsonResource::collection($plans);
    }

    /**
     * POST /api/plans
     * Creates a new subscription plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:plans,name|max:255',
            'price_monthly' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'status' => 'nullable|integer',
        ]);

        $plan = Plan::create($validated);

        return new JsonResource($plan);
    }
    
    /**
     * GET /api/plans/{plan}
     * Shows a single plan.
     */
    public function show(Plan $plan)
    {
        return new JsonResource($plan);
    }
    
    /**
     * PUT/PATCH /api/plans/{plan}
     * Updates an existing plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|unique:plans,name,' . $plan->id . '|max:255',
            'price_monthly' => 'sometimes|required|numeric|min:0',
            'features' => 'nullable|array',
            'status' => 'nullable|integer',
        ]);
        
        $plan->update($validated);
        
        return new JsonResource($plan);
    }

    /**
     * DELETE /api/plans/{plan}
     * Deletes a plan. Fails if active subscriptions reference it.
     */
    public function destroy(Plan $plan)
    {
        // Database foreign key constraint will prevent deletion if subscriptions exist.
        // If soft deleting is needed, the migration should be updated.
        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully.'], 200);
    }
}
