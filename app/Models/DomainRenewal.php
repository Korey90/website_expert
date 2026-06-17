<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'due_date',
        'years',
        'status',
        'retail_price',
        'currency',
        'stripe_payment_intent_id',
        'notified_30d',
        'notified_14d',
        'notified_7d',
        'notified_1d',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'years' => 'integer',
        'retail_price' => 'decimal:2',
        'notified_30d' => 'boolean',
        'notified_14d' => 'boolean',
        'notified_7d' => 'boolean',
        'notified_1d' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
