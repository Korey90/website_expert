<?php

namespace App\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    // Status constants
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    // Template constants
    public const TEMPLATE_LEAD_MAGNET = 'lead_magnet';
    public const TEMPLATE_SERVICES    = 'services';
    public const TEMPLATE_PORTFOLIO   = 'portfolio';

    protected $fillable = [
        'business_id',
        'default_assignee_id',
        'title',
        'description',
        'slug',
        'status',
        'template_key',
        'language',
        'meta_title',
        'meta_description',
        'og_image_path',
        'custom_css',
        'settings',
        'conversion_goal',
        'thank_you_url',
        'capture_fields',
        'views_count',
        'conversions_count',
        'ai_generated',
        'ai_generation_source',
        'current_generation_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated'      => 'boolean',
            'settings'          => 'array',
            'views_count'       => 'integer',
            'conversions_count' => 'integer',
            'published_at'      => 'datetime',
            'capture_fields'    => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(LandingPageSection::class)->orderBy('order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(LandingPageAiGeneration::class);
    }

    public function currentGeneration(): BelongsTo
    {
        return $this->belongsTo(LandingPageAiGeneration::class, 'current_generation_id');
    }

    public function formSection(): HasOne
    {
        return $this->hasOne(LandingPageSection::class)->where('type', 'form')->oldest('order');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeForBusiness($query, Business $business)
    {
        return $query->where('business_id', $business->id);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getPublicUrlAttribute(): string
    {
        return route('lp.show', ['slug' => $this->slug]);
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getSeoDescriptionAttribute(): ?string
    {
        return $this->meta_description;
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->views_count === 0) {
            return 0.0;
        }
        return round(($this->conversions_count / $this->views_count) * 100, 2);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function hasFormSection(): bool
    {
        return $this->sections()->where('type', 'form')->exists();
    }

    public function canBePublished(): bool
    {
        return $this->sections()->exists() && $this->hasFormSection();
    }

    public function publish(): void
    {
        $this->update([
            'status'       => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }
}
