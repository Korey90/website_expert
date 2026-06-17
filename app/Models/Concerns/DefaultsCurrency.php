<?php

namespace App\Models\Concerns;

use App\Services\Currency\CurrencyResolver;

trait DefaultsCurrency
{
    public static function bootDefaultsCurrency(): void
    {
        static::creating(function ($model): void {
            if (empty($model->currency)) {
                $model->currency = app(CurrencyResolver::class)->resolve(request());
            }
        });
    }
}
