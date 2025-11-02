<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Models\PropertyOwner;
use Illuminate\Auth\Access\Response;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('property:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Property $property): bool
    {
        return $user->hasPermissionTo('property:view') &&
               $property->propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, int $propertyOwnerId): bool
    {
        $propertyOwner = PropertyOwner::find($propertyOwnerId);
        return $user->hasPermissionTo('property:create') &&
               $propertyOwner &&
               $propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Property $property): bool
    {
        return $user->hasPermissionTo('property:update') &&
               $property->propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->hasPermissionTo('property:delete') &&
               $property->propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Property $property): bool
    {
        return $user->hasPermissionTo('property:restore') &&
               $property->propertyOwner->hosting_company_id === $user->hosting_company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Property $property): bool
    {
        return $user->hasPermissionTo('property:force-delete') &&
               $property->propertyOwner->hosting_company_id === $user->hosting_company_id;
    }
}
