<?php

namespace App\Models;

use App\Models\Concerns\DefaultsCurrency;
use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    use BelongsToTenant, DefaultsCurrency, HasFactory;

    protected $fillable = [
        'business_id', 'invoice_id', 'created_by', 'amount', 'amount_gbp', 'carrying_amount_gbp',
        'realised_fx_gbp', 'currency',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date', 'method', 'status',
        'stripe_payment_intent_id', 'reference', 'notes', 'paid_at', 'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_gbp' => 'decimal:2',
        'carrying_amount_gbp' => 'decimal:2',
        'realised_fx_gbp' => 'decimal:2',
        'gbp_exchange_rate' => 'decimal:8',
        'exchange_rate_date' => 'date',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Payment $payment): void {
            if ($payment->journalEntries()->exists() && $payment->isDirty([
                'business_id', 'invoice_id', 'amount', 'amount_gbp', 'carrying_amount_gbp',
                'realised_fx_gbp', 'currency',
                'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
                'method', 'paid_at',
            ])) {
                throw new DomainException('Posted payment accounting fields are immutable and must be reversed.');
            }
        });

        static::deleting(function (Payment $payment): void {
            if ($payment->journalEntries()->exists()) {
                throw new DomainException('A posted payment cannot be deleted.');
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }
}
