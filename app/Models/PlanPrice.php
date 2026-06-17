<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPrice extends Model
{
    public const INTERVAL_MONTHLY = 'monthly';

    public const INTERVAL_YEARLY = 'yearly';

    protected $fillable = [
        'plan_id',
        'currency',
        'interval',
        'amount_minor',
        'stripe_price_id',
        'is_active',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'is_active' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function setCurrencyAttribute(mixed $value): void
    {
        $currency = $value ?: config('currencies.default', 'GBP');

        $this->attributes['currency'] = strtoupper(trim((string) $currency));
    }

    public function setIntervalAttribute(mixed $value): void
    {
        $this->attributes['interval'] = strtolower(trim((string) $value));
    }

    public function getAmountDecimalAttribute(): float
    {
        return $this->amount_minor / 100;
    }
}
