<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * Accessor: Gets all permission names associated with the user's role.
     * This method is an attribute accessor, NOT an Eloquent relationship.
     * The permissions are accessed via $user->permissions.
     */
    public function getPermissionsAttribute()
    {
        // Use Caching to avoid hitting the database on every request
        return Cache::rememberForever("user.{$this->id}.permissions", function () {
            // Must load role and permissions to access the data
            $this->loadMissing('role.permissions');
            
            if ($this->role) {
                // Return a collection of permission names (e.g., ['property:view', 'role:manage'])
                return $this->role->permissions->pluck('name');
            }
            return collect();
        });
    }

    /**
     * New Method: Check if the user has the given permission name.
     * Renamed to avoid conflicts with Eloquent's default 'can' method.
     */
    public function canPermission($permissionName)
    {
        // Check the cached attribute/collection
        return $this->permissions->contains($permissionName);
    }

    /**
     * Override the default 'can' method to delegate to our custom check.
     * This is only needed if you did not put this logic into the User model directly.
     */
    public function can($abilities, $arguments = [])
    {
        // For simple string permissions (e.g., 'role:manage'), use our custom checker
        if (is_string($abilities)) {
            return $this->canPermission($abilities);
        }
        
        // For Policies or other complex checks, you would typically delegate to a parent check.
        return parent::can($abilities, $arguments);
    }
}