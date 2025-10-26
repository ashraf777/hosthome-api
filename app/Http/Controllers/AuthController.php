<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // NOTE: A User registering an account MUST have a role_id and hosting_company_id (provided here)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'hosting_company_id' => 'required|integer|exists:hosting_companies,id',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'hosting_company_id' => $request->hosting_company_id,
            'role_id' => $request->role_id,
            'status' => 1, // Default to inactive
        ]);

        $token = Str::random(64);
        $user->access_token = $token;
        $user->save();

        return response()->json([
            'message' => 'User successfully registered.',
            'access_token' => $token,
            'user' => new UserResource($user->load('role')),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->status !== 1) {
             return response()->json(['message' => 'Account is inactive or suspended.'], 403);
        }

        // Generate and store a custom access_token
        $token = Str::random(64);
        $user->access_token = $token;
        $user->save();
        
        return response()->json([
            'access_token' => $token,
            'user' => new UserResource($user->load('role')),
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->access_token = null;
            $user->save();
        }
        return response()->json(['message' => 'Successfully logged out.'], 200);
    }
}