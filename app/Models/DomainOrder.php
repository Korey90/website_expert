<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainOrder extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'client_id',
        'created_by',
        'domain_name',
        'tld',
        'full_domain',
        'years',
        'action',
        'status',
        'provider',
        'wholesale_price',
        'retail_price',
        'currency',
        'stripe_payment_intent_id',
        'auth_code',
        'notes',
        'admin_notes',
        'completed_at',
    ];

    protected $casts = [
        'years'           => 'integer',
        'wholesale_price' => 'decimal:2',
        'retail_price'    => 'decimal:2',
        'completed_at'    => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function domain(): HasOne
    {
        return $this->hasOne(Domain::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(DomainContact::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DomainEvent::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function salesOffers(): HasMany
    {
        return $this->hasMany(SalesOffer::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePendingPayment(Builder $query): Builder
    {
        return $query->where('status', 'pending_payment');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'registering', 'completed'], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
