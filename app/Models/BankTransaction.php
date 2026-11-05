<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransaction extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_UNMATCHED = 'unmatched';

    public const STATUS_PARTIALLY_RECONCILED = 'partially_reconciled';

    public const STATUS_RECONCILED = 'reconciled';

    protected $fillable = [
        'business_id', 'bank_account_id', 'bank_statement_import_id', 'provider_id',
        'booking_date', 'value_date', 'amount', 'currency', 'reference', 'counterparty',
        'source_hash', 'import_row_number', 'reconciliation_status',
    ];

    protected $hidden = ['source_hash'];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'value_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function reconciliationAllocations(): HasMany
    {
        return $this->hasMany(BankReconciliationAllocation::class);
    }
}
