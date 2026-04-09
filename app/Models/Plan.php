<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'max_landing_pages',
        'max_ai_per_month',
        'multi_user',
        'custom_domain',
        'ab_testing',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features'       => 'array',
        'multi_user'     => 'boolean',
        'custom_domain'  => 'boolean',
        'ab_testing'     => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class, 'plan', 'slug');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getPriceMonthlyDecimalAttribute(): float
    {
        return $this->price_monthly / 100;
    }

    public function getPriceYearlyDecimalAttribute(): float
    {
        return $this->price_yearly / 100;
    }

    public function getLandingPageLimitAttribute(): int|null
    {
        return $this->max_landing_pages; // null = unlimited
    }

    public function getAiLimitAttribute(): int|null
    {
        return $this->max_ai_per_month; // null = unlimited
    }

    public static function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
