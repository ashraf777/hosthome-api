<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/user (Authenticated user's profile)
     */
    public function show(Request $request)
    {
        return new UserResource($request->user()->load('role'));
    }

    /**
     * PUT /api/user (Update self-profile)
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $request->input('name', $user->name);
        $user->email = $request->input('email', $user->email);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        return new UserResource($user->load('role'));
    }

    /**
     * GET /api/users (List users within the same tenant)
     */
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('user:view')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }

        $companyId = $request->attributes->get('hosting_company_id');

        $users = User::where('hosting_company_id', $companyId)
            ->with('role')
            ->paginate(20);

        return UserResource::collection($users);
    }
    
    /**
     * PUT /api/users/{user}/role (Update staff role)
     */
    public function updateRole(Request $request, User $user)
    {
        if (!$request->user()->canPermission('user:assign-role')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }

        // Authorization check: Ensure user belongs to the same tenant
        $companyId = $request->attributes->get('hosting_company_id');
        if ($user->hosting_company_id !== $companyId) {
            return response()->json(['message' => 'User not found in tenant context.'], 404);
        }

        $request->validate(['role_id' => 'required|integer|exists:roles,id']);

        // You must ensure the new role is also scoped to the same tenant, 
        // or ensure your roles table design covers tenant scoping.
        $user->role_id = $request->role_id;
        $user->save();

        return new UserResource($user->load('role'));
    }
}