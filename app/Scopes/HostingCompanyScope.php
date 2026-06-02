<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class HostingCompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if we're in the console or if we explicitly disable tenant filtering
        if (app()->runningInConsole()) {
            return;
        }

        $hostingCompanyId = request()->attributes->get('hosting_company_id');

        if ($hostingCompanyId) {
            $builder->where($model->getTable() . '.hosting_company_id', $hostingCompanyId);
        }
    }
}
