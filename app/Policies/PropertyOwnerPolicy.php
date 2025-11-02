<?php

namespace App\Policies;

use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyOwnerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('property-owner:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PropertyOwner $propertyOwner): bool
    {
        return $user->hasPermissionTo('property-owner:view') &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('property-owner:create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PropertyOwner $propertyOwner): bool
    {
        return $user->hasPermissionTo('property-owner:update') &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PropertyOwner $propertyOwner): bool
    {
        return $user->hasPermissionTo('property-owner:delete') &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PropertyOwner $propertyOwner): bool
    {
        return $user->hasPermissionTo('property-owner:restore') &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PropertyOwner $propertyOwner): bool
    {
        return $user->hasPermissionTo('property-owner:force-delete') &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }
}
