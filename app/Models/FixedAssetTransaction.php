<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetTransaction extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_ACQUISITION = 'acquisition';

    public const TYPE_IMPROVEMENT = 'improvement';

    public const TYPE_DISPOSAL = 'disposal';

    public const TYPES = [
        self::TYPE_ACQUISITION,
        self::TYPE_IMPROVEMENT,
        self::TYPE_DISPOSAL,
    ];

    public const STATUS_PREPARED = 'prepared';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'business_id', 'fixed_asset_id', 'type', 'status', 'event_date',
        'expense_item_id', 'invoice_item_id', 'amount_gbp', 'cost_basis_gbp',
        'accumulated_depreciation_gbp', 'net_book_value_gbp', 'proceeds_gbp',
        'gain_loss_gbp', 'journal_entry_id', 'reversal_journal_entry_id',
        'created_by', 'reversed_by', 'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'amount_gbp' => 'decimal:2',
            'cost_basis_gbp' => 'decimal:2',
            'accumulated_depreciation_gbp' => 'decimal:2',
            'net_book_value_gbp' => 'decimal:2',
            'proceeds_gbp' => 'decimal:2',
            'gain_loss_gbp' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FixedAssetTransaction $transaction): void {
            if (! in_array($transaction->type, self::TYPES, true)) {
                throw new DomainException('Unsupported fixed asset transaction type.');
            }

            if ($transaction->status !== self::STATUS_PREPARED) {
                throw new DomainException('A fixed asset transaction must be prepared before posting.');
            }
        });

        static::updating(function (FixedAssetTransaction $transaction): void {
            if ($transaction->getOriginal('status') !== self::STATUS_PREPARED) {
                throw new DomainException('A posted fixed asset transaction is immutable and must be reversed.');
            }

            if ($transaction->status !== self::STATUS_POSTED) {
                throw new DomainException('A prepared fixed asset transaction can only become posted.');
            }
        });

        static::deleting(function (): void {
            throw new DomainException('Fixed asset transactions are permanent audit records.');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(ExpenseItem::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
