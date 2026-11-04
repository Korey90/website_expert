<?php

namespace App\Models;

use App\Models\Concerns\DefaultsCurrency;
use App\Services\Finance\DocumentTaxCalculator;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToTenant, DefaultsCurrency, HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'number', 'client_id', 'project_id', 'quote_id', 'domain_order_id', 'created_by',
        'status', 'currency', 'subtotal', 'discount_amount',
        'vat_rate', 'vat_amount', 'total', 'amount_paid', 'amount_due',
        'issue_date', 'tax_point', 'due_date', 'notes', 'terms',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
        'subtotal_gbp', 'vat_amount_gbp', 'total_gbp',
        'stripe_payment_link', 'sent_at', 'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'gbp_exchange_rate' => 'decimal:8',
        'subtotal_gbp' => 'decimal:2',
        'vat_amount_gbp' => 'decimal:2',
        'total_gbp' => 'decimal:2',
        'issue_date' => 'date',
        'tax_point' => 'date',
        'due_date' => 'date',
        'exchange_rate_date' => 'date',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }

    public function domainOrder(): BelongsTo
    {
        return $this->belongsTo(DomainOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->due_date->isPast();
    }

    public function recalculate(): void
    {
        $totals = app(DocumentTaxCalculator::class)->calculate(
            $this->items()->get(),
            (float) $this->discount_amount,
            (float) $this->vat_rate,
            $this->tax_point ?? $this->issue_date,
            $this->currency,
            $this->gbp_exchange_rate !== null ? (float) $this->gbp_exchange_rate : null,
        );
        $amountPaid = $this->payments()->where('status', 'completed')->sum('amount');
        $allocatedCredits = $this->creditNotes()
            ->whereIn('status', [CreditNote::STATUS_PARTIALLY_ALLOCATED, CreditNote::STATUS_ALLOCATED])
            ->sum('allocated_amount');
        $currencySnapshot = strtoupper((string) $this->currency) === 'GBP'
            ? [
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => $this->tax_point ?? $this->issue_date,
            ]
            : [];

        $this->update([
            ...$totals,
            ...$currencySnapshot,
            'tax_point' => $this->tax_point ?? $this->issue_date,
            'amount_paid' => $amountPaid,
            'amount_due' => max(0, $totals['total'] - $amountPaid - $allocatedCredits),
        ]);
    }
}
