<?php

namespace App\Traits;

use App\Scopes\HostingCompanyScope;

trait Multitenant
{
    public static function bootMultitenant()
    {
        static::addGlobalScope(new HostingCompanyScope);

        static::creating(function ($model) {
            if (!$model->hosting_company_id && request()->attributes->has('hosting_company_id')) {
                $model->hosting_company_id = request()->attributes->get('hosting_company_id');
            }
        });
    }
}
