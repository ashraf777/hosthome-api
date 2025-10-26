<?php

namespace App\Http\Controllers;

use App\Models\HostingCompany;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionController extends Controller
{
    /**
     * GET /api/hosting-companies/{hostingCompany}/subscriptions
     * Lists all subscriptions for a specific tenant.
     */
    public function index(HostingCompany $hostingCompany)
    {
        $subscriptions = $hostingCompany->subscriptions()->with('plan')->get();
        return JsonResource::collection($subscriptions);
    }

    /**
     * POST /api/hosting-companies/{hostingCompany}/subscriptions
     * Creates a new subscription record (e.g., plan change or renewal).
     */
    public function store(Request $request, HostingCompany $hostingCompany)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'started_at' => 'required|date',
            'expires_at' => 'nullable|date|after:started_at',
            'status' => 'required|in:active,expired,cancelled',
        ]);
        
        $subscription = $hostingCompany->subscriptions()->create($validated);

        // Update the current plan_id on the HostingCompany record
        $hostingCompany->update(['plan_id' => $request->plan_id]);

        return new JsonResource($subscription);
    }
    
    /**
     * GET /api/subscriptions/{subscription}
     * Shows a single subscription record.
     */
    public function show(Subscription $subscription)
    {
        // Since subscriptions can be accessed directly, ensure the route 
        // binding handles the ID correctly and load the related plan.
        return new JsonResource($subscription->load('plan'));
    }

    /**
     * PUT/PATCH /api/subscriptions/{subscription}
     * Updates an existing subscription status or expiry date.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_id' => 'sometimes|required|exists:plans,id',
            'started_at' => 'sometimes|required|date',
            'expires_at' => 'nullable|date|after:started_at',
            'status' => 'sometimes|required|in:active,expired,cancelled',
        ]);
        
        $subscription->update($validated);

        // If the updated subscription is active, ensure the hosting company's current plan_id is updated.
        if (isset($validated['status']) && $validated['status'] === 'active') {
             $subscription->hostingCompany->update(['plan_id' => $subscription->plan_id]);
        }

        return new JsonResource($subscription);
    }

    /**
     * DELETE /api/subscriptions/{subscription}
     * Deletes a subscription record.
     */
    public function destroy(Subscription $subscription)
    {
        // NOTE: Ensure business logic handles plan continuity before deletion.
        // For example, set the HostingCompany's current plan_id to NULL 
        // or the next active subscription if needed.
        
        $subscription->delete();
        
        return response()->json(['message' => 'Subscription deleted successfully.'], 200);
    }
}
