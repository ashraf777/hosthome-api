<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Role;
use App\Http\Resources\RoleResource;

class RoleController extends Controller
{
    // WARNING: Multi-tenancy scoping is DISABLED in this controller 
    // because the 'roles' table is missing the 'hosting_company_id' column.
    // All operations will currently affect ALL roles in the database.
    private function roleQuery()
    {
        // Return base Role model query without tenant scoping
        return Role::query();
    }

    /**
     * GET /api/roles
     * Lists all roles (platform-wide).
     */
    public function index(Request $request)
    {
        if (!$request->user()->canPermission('role:view')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }
        $roles = $this->roleQuery()->with('permissions')->get();
        return RoleResource::collection($roles);
    }

    /**
     * POST /api/roles
     * Creates a new role.
     */
    public function store(Request $request) // Use RoleRequest for validation
    {
        if (!$request->user()->canPermission('role:create')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }
        // UNIQUE check is simplified since we cannot scope by hosting_company_id
        $request->validate(['name' => 'required|string|max:255|unique:roles,name']);

        $role = $this->roleQuery()->create([
            'name' => $request->name,
            // 'hosting_company_id' => Auth::user()->hosting_company_id, // REMOVED
            'guard_name' => 'web',
        ]);

        return new RoleResource($role);
    }

    /**
     * PUT /api/roles/{role}
     * Updates an existing role's name.
     */
    public function update(Request $request, Role $role)
    {
        if (!$request->user()->canPermission('role:update')) {
             // If the user is authenticated but lacks permission
             return response()->json(['message' => 'This action is unauthorized. Permission platform:manage required.'], 403);
        }
        // REMOVED: if ($role->hosting_company_id !== Auth::user()->hosting_company_id) { abort(403, 'Unauthorized action.'); }
        
        // UNIQUE check is simplified since we cannot scope by hosting_company_id
        $request->validate(['name' => 'required|string|max:255|unique:roles,name,' . $role->id]);

        $role->update(['name' => $request->name]);

        return new RoleResource($role);
    }

    /**
     * POST /api/roles/{role}/sync-permissions
     * Synchronizes a list of permissions (IDs) to the role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        // Authorization check
        if (!$request->user()->canPermission('role:assign-permission')) {
            return response()->json(['message' => 'This action is unauthorized. Permission role:assign-permission required.'], 403);
        }
        
        // Validate that permission_ids is an array of integers
        $request->validate(['permission_ids' => 'required|array', 'permission_ids.*' => 'integer']);

        // --- MANUAL PIVOT TABLE SYNCHRONIZATION ---

        // 1. Delete all existing permissions for this role from the pivot table.
        DB::table('role_permission')->where('role_id', $role->id)->delete();

        // 2. Prepare the new permission data array.
        $insertData = [];
        foreach ($request->permission_ids as $permissionId) {
            $insertData[] = [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ];
        }
        
        // 3. Insert the new permission links into the pivot table.
        if (!empty($insertData)) {
            DB::table('role_permission')->insert($insertData);
        }

        // --- END MANUAL SYNCHRONIZATION ---

        // Reload role with permissions to confirm changes
        // NOTE: You must ensure RoleResource is using the correct PermissionResource
        return new RoleResource($role->load('permissions'));
    }
}
