<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOfferTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'service_slug',
        'language',
        'title',
        'description',
        'body',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function salesOffers(): HasMany
    {
        return $this->hasMany(SalesOffer::class, 'template_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness(Builder $query): Builder
    {
        $business = currentBusiness();

        return $query->where(function (Builder $q) use ($business) {
            $q->whereNull('business_id');
            if ($business) {
                $q->orWhere('business_id', $business->id);
            }
        });
    }

    public function scopeForService(Builder $query, string $slug): Builder
    {
        return $query->where('service_slug', $slug);
    }

    public function scopeForLanguage(Builder $query, string $lang): Builder
    {
        return $query->where('language', $lang);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isGlobal(): bool
    {
        return $this->business_id === null;
    }
}
