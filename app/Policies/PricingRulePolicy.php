<?php

namespace App\Policies;

use App\Models\PricingRule;
use App\Models\User;
use App\Models\RoomType;
use Illuminate\Auth\Access\Response;

class PricingRulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pricing:view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PricingRule $pricingRule): bool
    {
        return $user->hosting_company_id === $pricingRule->roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, int $roomTypeId): bool
    {
        $roomType = RoomType::findOrFail($roomTypeId);
        return $user->hosting_company_id === $roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PricingRule $pricingRule): bool
    {
        return $user->hosting_company_id === $pricingRule->roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PricingRule $pricingRule): bool
    {
        return $user->hosting_company_id === $pricingRule->roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PricingRule $pricingRule): bool
    {
        return $user->hosting_company_id === $pricingRule->roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PricingRule $pricingRule): bool
    {
        return $user->hosting_company_id === $pricingRule->roomType->property->hosting_company_id
            && $user->hasPermissionTo('pricing:admin');
    }
}
