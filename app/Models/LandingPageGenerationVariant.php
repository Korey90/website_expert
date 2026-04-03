<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageGenerationVariant extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'generation_id',
        'user_id',
        'title',
        'slug_suggestion',
        'language',
        'template_key',
        'meta',
        'sections',
        'is_saved',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sections' => 'array',
            'is_saved' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(LandingPageAiGeneration::class, 'generation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnsaved($query)
    {
        return $query->where('is_saved', false);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($builder) {
            $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}