<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getLastActivityAtAttribute(): Carbon
    {
        return Carbon::createFromTimestamp($this->last_activity);
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->id === session()->getId();
    }

    public function getBrowserAttribute(): string
    {
        $ua = $this->user_agent ?? '';

        if (str_contains($ua, 'Edg/'))    return 'Edge';
        if (str_contains($ua, 'Chrome/')) return 'Chrome';
        if (str_contains($ua, 'Firefox/')) return 'Firefox';
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Safari/')) return 'Safari';

        return 'Unknown';
    }

    public function getDeviceTypeAttribute(): string
    {
        $ua = strtolower($this->user_agent ?? '');

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeAuthenticated(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeGuest(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeActive(Builder $query, int $minutes = 120): Builder
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp);
    }
}
