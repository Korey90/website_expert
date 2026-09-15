<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchRankingCheck extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'keyword',
        'domain',
        'location',
        'location_code',
        'device',
        'language',
        'status',
        'position',
        'ranking_url',
        'provider',
        'from_cache',
        'ip_hash',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'from_cache' => 'boolean',
        'created_at' => 'datetime',
    ];

    public const STATUS_FOUND = 'found';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_FAILED = 'failed';

    public const DEVICES = ['desktop', 'mobile'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
