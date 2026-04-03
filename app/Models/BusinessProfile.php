<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $table = 'business_profiles';

    protected $fillable = [
        'business_id',
        'tagline',
        'description',
        'industry',
        'tone_of_voice',
        'target_audience',
        'services',
        'brand_colors',
        'fonts',
        'website_url',
        'social_links',
        'seo_keywords',
        'ai_context_cache',
        'ai_context_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'target_audience'       => 'array',
            'services'              => 'array',
            'brand_colors'          => 'array',
            'fonts'                 => 'array',
            'social_links'          => 'array',
            'seo_keywords'          => 'array',
            'ai_context_updated_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getPrimaryColorAttribute(): string
    {
        return $this->brand_colors['primary']
            ?? $this->business?->primary_color
            ?? '#3b82f6';
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function isAiCacheStale(): bool
    {
        return ! $this->ai_context_updated_at
            || $this->ai_context_updated_at->diffInHours(now()) > 24;
    }

    public function toAiContext(): array
    {
        return [
            'brand_name'      => $this->business?->name,
            'tagline'         => $this->tagline,
            'industry'        => $this->industry,
            'tone_of_voice'   => $this->tone_of_voice ?? 'professional',
            'target_audience' => $this->target_audience ?? [],
            'services'        => $this->services ?? [],
            'primary_color'   => $this->getPrimaryColorAttribute(),
            'language'        => $this->business?->locale ?? 'en',
        ];
    }

    public function isComplete(): bool
    {
        return filled($this->tagline)
            && filled($this->industry)
            && filled($this->tone_of_voice)
            && ! empty($this->services);
    }
}
