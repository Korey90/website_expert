<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPageAiGeneration extends Model
{
    use HasFactory, BelongsToTenant;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'business_id',
        'landing_page_id',
        'user_id',
        'status',
        'source',
        'model',
        'input_payload',
        'normalized_payload',
        'error_code',
        'error_message',
        'tokens_input',
        'tokens_output',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'normalized_payload' => 'array',
            'tokens_input' => 'integer',
            'tokens_output' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(LandingPageGenerationVariant::class, 'generation_id');
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', self::STATUS_SUCCEEDED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRecent($query)
    {
        return $query->latest('id');
    }
}