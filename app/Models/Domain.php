<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Domain extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'client_id',
        'domain_order_id',
        'provider',
        'provider_domain_id',
        'name',
        'tld',
        'full_domain',
        'status',
        'registered_at',
        'expires_at',
        'auto_renew',
        'whois_privacy',
        'nameservers',
        'dns_records',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'expires_at'    => 'date',
        'auto_renew'    => 'boolean',
        'whois_privacy' => 'boolean',
        'nameservers'   => 'array',
        'dns_records'   => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function domainOrder(): BelongsTo
    {
        return $this->belongsTo(DomainOrder::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(DomainRenewal::class)->orderByDesc('due_date');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DomainEvent::class)->orderByDesc('created_at');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isBetween(Carbon::now(), Carbon::now()->addDays($days));
    }

    public function daysUntilExpiry(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }
}
