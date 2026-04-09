<?php

namespace App\Traits;

use App\Scopes\BusinessScope;

/**
 * BelongsToTenant — multi-tenancy GlobalScope enforcement.
 *
 * Behaviour:
 *  - Auto-fill business_id on model creation from currentBusiness().
 *  - Applies BusinessScope GlobalScope to all queries (tenant isolation).
 *  - Admin/Filament resources must use withoutGlobalScope(BusinessScope::class)
 *    where cross-tenant visibility is required.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Auto-fill business_id on creation
        static::creating(function (self $model): void {
            if (empty($model->business_id)) {
                $model->business_id = currentBusiness()?->id;
            }
        });

        // Activate GlobalScope tenant isolation (v1.1)
        static::addGlobalScope(new BusinessScope());
    }
}
