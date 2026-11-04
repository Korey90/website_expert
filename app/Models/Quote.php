<?php

namespace App\Models;

use App\Models\Concerns\DefaultsCurrency;
use App\Services\Finance\DocumentTaxCalculator;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use BelongsToTenant, DefaultsCurrency, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'number', 'client_id', 'lead_id', 'domain_order_id', 'created_by',
        'status', 'currency', 'subtotal', 'discount_amount',
        'vat_rate', 'vat_amount', 'total', 'notes', 'terms',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
        'subtotal_gbp', 'vat_amount_gbp', 'total_gbp',
        'valid_until', 'sent_at', 'accepted_at', 'rejected_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'gbp_exchange_rate' => 'decimal:8',
        'subtotal_gbp' => 'decimal:2',
        'vat_amount_gbp' => 'decimal:2',
        'total_gbp' => 'decimal:2',
        'exchange_rate_date' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('order');
    }

    public function domainOrder(): BelongsTo
    {
        return $this->belongsTo(DomainOrder::class);
    }

    public function recalculate(): void
    {
        $totals = app(DocumentTaxCalculator::class)->calculate(
            $this->items()->get(),
            (float) $this->discount_amount,
            (float) $this->vat_rate,
            null,
            $this->currency,
            $this->gbp_exchange_rate !== null ? (float) $this->gbp_exchange_rate : null,
        );

        $currencySnapshot = strtoupper((string) $this->currency) === 'GBP'
            ? [
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => $this->exchange_rate_date ?? $this->created_at?->toDateString() ?? today(),
            ]
            : [];

        $this->update([
            ...$totals,
            ...$currencySnapshot,
        ]);
    }
}
