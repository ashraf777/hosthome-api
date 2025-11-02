<?php

namespace App\Http\Controllers;

use App\Models\HostingCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class HostingCompanyController extends Controller
{
    /**
     * GET /api/hosting-companies
     * Lists all SaaS tenants.
     */
    public function index(Request $request)
    {
        // CORRECT: Super Admin Permission Check
        // if (!$request->user()->canPermission('hosting-company:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $companies = HostingCompany::with(['country', 'plan'])->paginate(20);
        
        return JsonResource::collection($companies);
    }

    /**
     * POST /api/hosting-companies
     * Creates a new tenant account (Master DB entry only).
     */
    public function store(Request $request)
    {
        // CORRECT: Super Admin Permission Check
        // if (!$request->user()->canPermission('hosting-company:create')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:hosting_companies,contact_email',
            'country_id' => 'required|exists:countries,id',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        $slug = Str::slug($request->name);
        
        // Setup initial provisioning data
        $companyData = array_merge($validated, [
            'slug' => $slug,
            'status' => 'trial', 
            // NOTE: DB details are placeholders until actual provisioning script runs
            'db_host' => env('DB_HOST', '127.0.0.1'), 
            'db_name' => 'hosthome_' . $slug,
            'db_user' => 'tenant_user_' . Str::random(8),
        ]);

        $company = HostingCompany::create($companyData);

        // NOTE: A separate job/event should be fired here to provision the Production DB schema.

        return new JsonResource($company);
    }
    
    /**
     * GET /api/hosting-companies/{hostingCompany}
     */
    public function show(Request $request, HostingCompany $hostingCompany)
    {
        // CORRECT: Super Admin Permission Check
        // if (!$request->user()->canPermission('hosting-company:view')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        return new JsonResource($hostingCompany->load(['country', 'plan']));
    }

    /**
     * PUT/PATCH /api/hosting-companies/{hostingCompany}
     * Updates tenant status or plan.
     */
    public function update(Request $request, HostingCompany $hostingCompany)
    {
        // CORRECT: Super Admin Permission Check
        // if (!$request->user()->canPermission('hosting-company:update')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        $validated = $request->validate([
            'name' => 'sometimes|required|max:255',
            'contact_email' => 'sometimes|required|email|unique:hosting_companies,contact_email,' . $hostingCompany->id,
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'sometimes|required|in:active,suspended,trial',
        ]);
        
        $hostingCompany->update($validated);
        
        return new JsonResource($hostingCompany);
    }

    /**
     * DELETE /api/hosting-companies/{hostingCompany}
     * Deletes the company record. Fails if active users or subscriptions exist.
     */
    public function destroy(Request $request, HostingCompany $hostingCompany)
    {
        // CORRECT: Super Admin Permission Check
        // if (!$request->user()->canPermission('hosting-company:delete')) {
        //     return response()->json(['message' => 'This action is unauthorized.'], 403);
        // }

        // NOTE: In a real SaaS app, this would involve a complex process:
        // 1. Terminating all subscriptions.
        // 2. Deleting all users.
        // 3. Dropping the Production DB schema.
        // 4. Finally, deleting the Master DB record.

        $hostingCompany->delete();
        
        return response()->json(['message' => 'Hosting company record deleted successfully.'], 200);
    }
}
