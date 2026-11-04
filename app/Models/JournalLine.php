<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'journal_entry_id', 'ledger_account_id', 'tax_code_id', 'client_id',
        'supplier_id', 'project_id', 'line_number', 'debit_gbp', 'credit_gbp',
        'original_currency', 'original_amount', 'exchange_rate', 'memo', 'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'debit_gbp' => 'decimal:2',
            'credit_gbp' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'dimensions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JournalLine $line): void {
            $entry = JournalEntry::withoutGlobalScopes()->find($line->journal_entry_id);
            $account = LedgerAccount::withoutGlobalScopes()->find($line->ledger_account_id);

            if (! $entry || $entry->business_id !== $line->business_id) {
                throw new DomainException('The journal line must belong to its journal business.');
            }

            if (! $account || $account->business_id !== $line->business_id || ! $account->is_active || ! $account->is_postable) {
                throw new DomainException('The journal line requires an active postable account from the same business.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }
}
