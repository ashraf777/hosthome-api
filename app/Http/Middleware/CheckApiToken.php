<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        // dd($token);
        if (!$token) {
            return response()->json(['message' => 'Authentication token missing.'], 401);
        }

        $user = User::where('access_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired access token.'], 401);
        }

        // 1. Enforce Status Check
        if ($user->status !== 1) {
            return response()->json(['message' => 'Account is inactive or suspended.'], 403);
        }

        // 2. Enforce Tenant Context
        if (!$user->hosting_company_id) {
            return response()->json(['message' => 'User is not linked to a Hosting Company.'], 403);
        }

        // Set the authenticated user and tenant context
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->attributes->set('hosting_company_id', $user->hosting_company_id);

        return $next($request);
    }
}