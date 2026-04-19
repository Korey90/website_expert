<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOffer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'lead_id',
        'template_id',
        'created_by',
        'client_token',
        'title',
        'language',
        'body',
        'status',
        'sent_at',
        'viewed_at',
        'cta_clicked_at',
        'notes',
    ];

    protected $casts = [
        'sent_at'        => 'datetime',
        'viewed_at'      => 'datetime',
        'cta_clicked_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SalesOfferTemplate::class, 'template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForBusiness(Builder $query): Builder
    {
        $business = currentBusiness();

        if ($business) {
            $query->where('business_id', $business->id);
        }

        return $query;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'viewed', 'converted']);
    }
}
