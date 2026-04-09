<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * BusinessScope — GlobalScope for tenant isolation.
 *
 * Automatically filters all queries on BelongsToTenant models by the current
 * business (tenant). Admin/staff queries must call withoutGlobalScope() or
 * use dedicated admin resources (Filament already handles this via getEloquentQuery).
 *
 * Skipped when:
 *  - No current business is resolved (unauthenticated, webhook, console commands)
 *  - Model has a null business_id column (cross-tenant aggregates)
 */
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $business = currentBusiness();

        if ($business === null) {
            return;
        }

        $table = $model->getTable();

        $builder->where("{$table}.business_id", $business->id);
    }
}
