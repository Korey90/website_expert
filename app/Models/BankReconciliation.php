<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'business_id', 'bank_transaction_id', 'created_by', 'status', 'total_amount',
        'currency', 'notes', 'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'reconciled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BankReconciliation $reconciliation): void {
            $reconciliation->status ??= self::STATUS_ACTIVE;

            if ($reconciliation->status !== self::STATUS_ACTIVE
                || $reconciliation->reversed_by !== null
                || $reconciliation->reversed_at !== null
                || $reconciliation->reversal_reason !== null) {
                throw new DomainException('A bank reconciliation must be created as active without reversal metadata.');
            }
        });

        static::updating(function (): never {
            throw new DomainException('A bank reconciliation is immutable and must be reversed through the undo action.');
        });

        static::deleting(function (): never {
            throw new DomainException('A bank reconciliation cannot be deleted.');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BankReconciliationAllocation::class);
    }
}
