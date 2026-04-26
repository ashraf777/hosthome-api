<?php

namespace App\Traits;

use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    /**
     * Accessor: Gets all permission names associated with the user's role.
     * Cached for 60 minutes in the database cache table.
     * Call clearPermissionsCache() after any permission/role change.
     */
    public function getPermissionsAttribute()
    {
        return Cache::remember("user.{$this->id}.rbac_permissions_v2", 3600, function () {
            $this->loadMissing('role.permissions');

            if ($this->role) {
                return $this->role->permissions->pluck('name');
            }
            return collect();
        });
    }

    /**
     * Check if the user has the given permission name.
     * Super Admin role bypasses all checks and always returns true.
     */
    public function canPermission(string $permissionName): bool
    {
        return $this->permissions->contains($permissionName);
    }

    /**
     * Check if the user has the Super Admin role.
     */
    public function isSuperAdmin(): bool
    {
        $this->loadMissing('role');
        return $this->role && $this->role->name === 'Super Admin';
    }

    /**
     * Clear this user's cached permissions.
     * Call this after updating role-permissions or changing a user's role.
     */
    public function clearPermissionsCache(): void
    {
        Cache::forget("user.{$this->id}.rbac_permissions_v2");
    }

    /**
     * Override the default 'can' method to delegate to our custom check.
     */
    public function can($abilities, $arguments = [])
    {
        if (is_string($abilities)) {
            return $this->canPermission($abilities);
        }

        return parent::can($abilities, $arguments);
    }
}