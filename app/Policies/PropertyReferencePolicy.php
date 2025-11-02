<?php

namespace App\Policies;

use App\Models\PropertyReference;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyReferencePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('property-reference:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyReference $propertyReference): bool
    {
        return $user->hasPermissionTo('property-reference:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('property-reference:admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PropertyReference $propertyReference): bool
    {
        return $user->hasPermissionTo('property-reference:admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PropertyReference $propertyReference): bool
    {
        return $user->hasPermissionTo('property-reference:admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyReference $propertyReference): bool
    {
        return $user->hasPermissionTo('property-reference:admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyReference $propertyReference): bool
    {
        return $user->hasPermissionTo('property-reference:admin');
    }
}
