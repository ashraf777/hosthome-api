<?php

namespace App\Http\Controllers;

use App\Models\Permission; // Assuming you created this model
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('platform:manage')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }
        // Permissions are system-wide, so no tenant scoping is needed here.
        $permissions = Permission::where('guard_name', 'web')->get();
        
        // Assume PermissionResource is a basic formatter returning ID and name
        return PermissionResource::collection($permissions);
    }
}