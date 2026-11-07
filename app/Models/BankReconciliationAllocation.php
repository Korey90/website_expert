<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankReconciliationAllocation extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'bank_reconciliation_id', 'bank_transaction_id',
        'reconcilable_type', 'reconcilable_id', 'event_type', 'candidate_key',
        'amount', 'currency', 'source_amount', 'source_currency', 'match_score',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'source_amount' => 'decimal:2',
            'match_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new DomainException('A bank reconciliation allocation is immutable.');
        });

        static::deleting(function (): never {
            throw new DomainException('A bank reconciliation allocation cannot be deleted.');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function reconcilable(): MorphTo
    {
        return $this->morphTo();
    }
}
