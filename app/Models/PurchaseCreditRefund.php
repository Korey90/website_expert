<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseCreditRefund extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'purchase_credit_note_id', 'expense_id', 'received_by',
        'amount', 'amount_gbp', 'currency', 'gbp_exchange_rate', 'exchange_rate_source',
        'exchange_rate_date', 'method', 'reference', 'received_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_gbp' => 'decimal:2',
            'gbp_exchange_rate' => 'decimal:8',
            'exchange_rate_date' => 'date',
            'received_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (PurchaseCreditRefund $refund): void {
            if ($refund->journalEntries()->exists()) {
                throw new DomainException('A posted supplier refund is immutable and must be reversed.');
            }
        });

        static::deleting(function (PurchaseCreditRefund $refund): void {
            if ($refund->journalEntries()->exists()) {
                throw new DomainException('A posted supplier refund cannot be deleted.');
            }
        });
    }

    public function purchaseCreditNote(): BelongsTo
    {
        return $this->belongsTo(PurchaseCreditNote::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class)->withTrashed();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }
}
