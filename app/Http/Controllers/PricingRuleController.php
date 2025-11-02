<?php

namespace App\Http\Controllers;

use App\Models\PricingRule;
use App\Models\RoomType;
use Illuminate\Http\Request;
use App\Http\Resources\PricingRuleResource;
use Illuminate\Validation\Rule;

class PricingRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // CORRECT: Permission Check
        if (!$request->user()->canPermission('pricing-rule:view')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        // CORRECT: Tenancy Check (already existed and was correct)
        $pricingRules = PricingRule::whereHas('roomType.property', function ($query) use ($request) {
            $query->where('hosting_company_id', $request->user()->hosting_company_id);
        })->latest()->paginate();

        return PricingRuleResource::collection($pricingRules);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'name' => 'required|string|max:255',
            'rule_type' => ['required', Rule::in(['base', 'markup', 'seasonal', 'event', 'last_minute'])],
            'price_modifier' => 'nullable|numeric',
            'modifier_type' => ['required', Rule::in(['fixed', 'percentage', 'fixed_price'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'day_of_week' => 'nullable|string|max:255',
            'status' => 'required|integer|in:0,1',
        ]);

        // CORRECT: Tenancy Check - ensure the room type belongs to the user's company.
        RoomType::where('id', $validated['room_type_id'])
            ->whereHas('property', function ($query) use ($request) {
                $query->where('hosting_company_id', $request->user()->hosting_company_id);
            })
            ->firstOrFail(); // Fails with 404 if not found or not owned.

        // CORRECT: Permission Check
        if (!$request->user()->canPermission('pricing-rule:create')) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $pricingRule = PricingRule::create($validated);

        return new PricingRuleResource($pricingRule);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, PricingRule $pricingRule)
    {
        // CORRECT: Tenancy Check + Permission Check
        $this->authorizeTenancyAndPermission($request, $pricingRule, 'pricing-rule:view');

        return new PricingRuleResource($pricingRule);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PricingRule $pricingRule)
    {
        // CORRECT: Tenancy Check + Permission Check
        $this->authorizeTenancyAndPermission($request, $pricingRule, 'pricing-rule:update');

        $validated = $request->validate([
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'name' => 'sometimes|required|string|max:255',
            'rule_type' => ['sometimes','required', Rule::in(['base', 'markup', 'seasonal', 'event', 'last_minute'])],
            'price_modifier' => 'nullable|numeric',
            'modifier_type' => ['sometimes','required', Rule::in(['fixed', 'percentage', 'fixed_price'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'day_of_week' => 'nullable|string|max:255',
            'status' => 'sometimes|required|integer|in:0,1',
        ]);

        $pricingRule->update($validated);

        return new PricingRuleResource($pricingRule);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, PricingRule $pricingRule)
    {
        // CORRECT: Tenancy Check + Permission Check
        $this->authorizeTenancyAndPermission($request, $pricingRule, 'pricing-rule:delete');

        $pricingRule->delete();

        return response()->noContent();
    }

    /**
     * Helper to authorize tenancy and permission for a given pricing rule.
     */
    private function authorizeTenancyAndPermission(Request $request, PricingRule $pricingRule, string $permission)
    {
        // CORRECT: Tenancy check by re-fetching the model within the user's company scope.
        $rule = PricingRule::where('id', $pricingRule->id)
                           ->whereHas('roomType.property', function ($query) use ($request) {
                               $query->where('hosting_company_id', $request->user()->hosting_company_id);
                           })
                           ->firstOrFail(); // This will 404 if the rule doesn't exist or belong to the user.

        // CORRECT: Use the application's canPermission() method for authorization.
        if (!$request->user()->canPermission($permission)) {
             abort(403, 'This action is unauthorized.');
        }
    }
}
