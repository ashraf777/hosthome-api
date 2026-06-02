<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\HostingCompany;
use Symfony\Component\HttpFoundation\Response;

class SetTenantBySlug
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('company_slug');

        if (!$slug) {
            return response()->json(['message' => 'Hosting company slug missing.'], 400);
        }

        $company = HostingCompany::where('slug', $slug)->first();

        if (!$company) {
            return response()->json(['message' => 'Hosting company not found.'], 404);
        }

        if ($company->status !== 'active' && $company->status !== 'trial') {
            return response()->json(['message' => 'Hosting company is suspended.'], 403);
        }

        $request->attributes->set('hosting_company_id', $company->id);

        return $next($request);
    }
}
