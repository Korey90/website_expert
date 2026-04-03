<?php

namespace App\Traits;

/**
 * BelongsToTenant — skeleton for future multi-tenancy GlobalScope enforcement.
 *
 * MVP behaviour: auto-fill business_id on model creation from currentBusiness().
 * v1.1 behaviour: uncomment addGlobalScope line to enforce per-tenant isolation.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->business_id)) {
                $model->business_id = currentBusiness()?->id;
            }
        });

        // v1.1: uncomment to activate GlobalScope tenant isolation
        // static::addGlobalScope(new \App\Scopes\BusinessScope());
    }
}
