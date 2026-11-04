<?php

namespace App\Models;

use App\Services\Finance\PurchaseTaxCalculator;
use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_APPROVED,
            self::STATUS_POSTED,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_VOID,
        ];
    }

    protected $fillable = [
        'business_id', 'supplier_id', 'client_id', 'project_id', 'recurring_expense_template_id', 'recurrence_date',
        'created_by', 'approved_by', 'posted_by', 'voided_by',
        'supplier_invoice_number', 'status', 'currency',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
        'issue_date', 'tax_point', 'due_date', 'received_date',
        'subtotal', 'vat_amount', 'total', 'subtotal_gbp', 'vat_amount_gbp', 'total_gbp',
        'amount_paid', 'amount_due', 'payment_method', 'business_purpose', 'notes',
        'void_reason', 'approved_at', 'posted_at', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'gbp_exchange_rate' => 'decimal:8',
            'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2',
            'subtotal_gbp' => 'decimal:2', 'vat_amount_gbp' => 'decimal:2', 'total_gbp' => 'decimal:2',
            'amount_paid' => 'decimal:2', 'amount_due' => 'decimal:2',
            'issue_date' => 'date', 'tax_point' => 'date', 'due_date' => 'date', 'recurrence_date' => 'date',
            'received_date' => 'date', 'exchange_rate_date' => 'date',
            'approved_at' => 'datetime', 'posted_at' => 'datetime', 'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Expense $expense): void {
            if ($expense->journalEntries()->exists() && $expense->isDirty([
                'business_id', 'supplier_id', 'client_id', 'project_id', 'supplier_invoice_number',
                'currency', 'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
                'issue_date', 'tax_point', 'subtotal', 'vat_amount', 'total', 'subtotal_gbp',
                'vat_amount_gbp', 'total_gbp',
            ])) {
                throw new DomainException('Posted expense accounting fields are immutable and require a purchase credit note.');
            }
        });

        static::deleting(function (Expense $expense): void {
            if ($expense->journalEntries()->exists()) {
                throw new DomainException('A posted expense cannot be deleted.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(RecurringExpenseTemplate::class, 'recurring_expense_template_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseItem::class)->orderBy('order');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function purchaseCreditNotes(): HasMany
    {
        return $this->hasMany(PurchaseCreditNote::class);
    }

    public function purchaseCreditRefunds(): HasMany
    {
        return $this->hasMany(PurchaseCreditRefund::class);
    }

    public function recalculate(): void
    {
        $totals = app(PurchaseTaxCalculator::class)->calculate(
            $this->items()->get(),
            $this->tax_point ?? $this->issue_date,
            $this->currency,
            $this->gbp_exchange_rate !== null ? (float) $this->gbp_exchange_rate : null,
        );
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
            'amount_due' => max(0, round($totals['total'] - (float) $this->amount_paid, 2)),
        ]);
    }
}
