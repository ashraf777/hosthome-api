<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Models\PropertyUnit;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('booking:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->hosting_company_id === $booking->propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, int $propertyUnitId): bool
    {
        $propertyUnit = PropertyUnit::findOrFail($propertyUnitId);
        return $user->hosting_company_id === $propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->hosting_company_id === $booking->propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->hosting_company_id === $booking->propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
         return $user->hosting_company_id === $booking->propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
         return $user->hosting_company_id === $booking->propertyUnit->roomType->property->hosting_company_id
            && $user->hasPermissionTo('booking:admin');
    }
}
