<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanerTokenCheck
{
    /**
     * Handle an incoming request.
     * Validates Bearer token against users.access_token,
     * and ensures the user has the 'Staff/Cleaner' role and is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated. No token provided.'], 401);
        }

        $user = User::where('access_token', $token)
            ->with('role')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated. Invalid token.'], 401);
        }

        if ($user->status !== 1) {
            return response()->json(['message' => 'Account is inactive or suspended.'], 403);
        }

        if ($user->role->name !== 'Staff/Cleaner') {
            return response()->json(['message' => 'Access denied. Cleaner role required.'], 403);
        }

        // Bind the resolved user to the request (same pattern as api.token.check)
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
