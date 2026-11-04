<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'expense_id', 'tax_code_id', 'ledger_account_id', 'category', 'client_id', 'project_id',
        'description', 'quantity', 'unit_price', 'supply_date',
        'net_amount', 'net_amount_gbp', 'vat_rate_snapshot',
        'vat_amount', 'vat_amount_gbp', 'gross_amount', 'gross_amount_gbp',
        'billable', 'order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'supply_date' => 'date',
            'net_amount' => 'decimal:2', 'net_amount_gbp' => 'decimal:2',
            'vat_rate_snapshot' => 'decimal:2', 'vat_amount' => 'decimal:2',
            'vat_amount_gbp' => 'decimal:2', 'gross_amount' => 'decimal:2',
            'gross_amount_gbp' => 'decimal:2', 'billable' => 'boolean', 'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExpenseItem $item): void {
            if (empty($item->business_id) && $item->expense_id) {
                $item->business_id = Expense::withoutGlobalScopes()->find($item->expense_id)?->business_id;
            }

            $item->assertExpenseIsUnposted();
        });

        static::updating(function (ExpenseItem $item): void {
            $item->assertExpenseIsUnposted();
        });

        static::deleting(function (ExpenseItem $item): void {
            $item->assertExpenseIsUnposted();
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class)->withTrashed();
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    private function assertExpenseIsUnposted(): void
    {
        if (! $this->expense_id) {
            return;
        }

        $expense = Expense::withoutGlobalScopes()->find($this->expense_id);
        if ($expense?->journalEntries()->exists()) {
            throw new DomainException('Posted expense items are immutable and require a purchase credit note.');
        }
    }
}
