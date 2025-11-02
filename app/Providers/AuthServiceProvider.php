<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Channel;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\PropertyReference;
use App\Policies\BookingPolicy;
use App\Policies\ChannelPolicy;
use App\Policies\PricingRulePolicy;
use App\Policies\PropertyOwnerPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\PropertyReferencePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Property::class => PropertyPolicy::class,
        PropertyOwner::class => PropertyOwnerPolicy::class,
        PropertyReference::class => PropertyReferencePolicy::class,
        Channel::class => ChannelPolicy::class,
        Booking::class => BookingPolicy::class,
        PricingRule::class => PricingRulePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
