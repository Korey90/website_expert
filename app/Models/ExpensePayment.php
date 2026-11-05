<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpensePayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'expense_id', 'bank_account_id', 'created_by', 'amount', 'currency', 'amount_gbp',
        'carrying_amount_gbp', 'realised_fx_gbp',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date', 'method',
        'reference', 'notes', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_gbp' => 'decimal:2',
            'carrying_amount_gbp' => 'decimal:2',
            'realised_fx_gbp' => 'decimal:2',
            'gbp_exchange_rate' => 'decimal:8',
            'exchange_rate_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ExpensePayment $payment): void {
            if ($payment->journalEntries()->exists()) {
                throw new DomainException('A posted supplier payment is immutable and must be reversed.');
            }
        });

        static::deleting(function (ExpensePayment $payment): void {
            if ($payment->journalEntries()->exists()) {
                throw new DomainException('A posted supplier payment cannot be deleted.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class)->withTrashed();
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

    public function bankReconciliationAllocations(): MorphMany
    {
        return $this->morphMany(BankReconciliationAllocation::class, 'reconcilable');
    }
}
