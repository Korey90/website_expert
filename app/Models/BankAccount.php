<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'business_id', 'ledger_account_id', 'name', 'bank_name', 'account_number_last4',
        'sort_code_last2', 'account_fingerprint', 'currency', 'opening_balance',
        'opening_balance_date', 'status', 'last_synced_at', 'created_by', 'archived_by', 'archived_at',
    ];

    protected $hidden = ['account_fingerprint'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_date' => 'date',
            'last_synced_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BankAccount $account): void {
            if (! preg_match('/^\d{4}$/', (string) $account->account_number_last4)
                || ! preg_match('/^\d{2}$/', (string) $account->sort_code_last2)
                || ! preg_match('/^[a-f0-9]{64}$/', (string) $account->account_fingerprint)) {
                throw new DomainException('Bank details must be stored only as protected masked values.');
            }

            if (! in_array($account->status, [self::STATUS_ACTIVE, self::STATUS_ARCHIVED], true)) {
                throw new DomainException('The bank account status is invalid.');
            }

            $ledgerAccount = LedgerAccount::withoutGlobalScopes()->find($account->ledger_account_id);

            if (! $ledgerAccount
                || $ledgerAccount->business_id !== $account->business_id
                || $ledgerAccount->type !== 'asset'
                || $ledgerAccount->subtype !== 'cash'
                || ! $ledgerAccount->is_postable
                || ! $ledgerAccount->is_active) {
                throw new DomainException('A bank account requires an active cash ledger account from the same business.');
            }
        });

        static::deleting(function (): void {
            throw new DomainException('A bank account cannot be deleted; archive it instead.');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function statementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getMaskedAccountNumberAttribute(): string
    {
        return '••••'.$this->account_number_last4;
    }

    public function getMaskedSortCodeAttribute(): string
    {
        return '••-••-'.$this->sort_code_last2;
    }
}
