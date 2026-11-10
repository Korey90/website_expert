<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VatAdjustment extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const TYPE_ACCOUNTING_ADJUSTMENT = 'accounting_adjustment';

    public const TYPE_ERROR_CORRECTION = 'error_correction';

    public const SIDE_OUTPUT = 'output';

    public const SIDE_INPUT = 'input';

    public const DIRECTION_INCREASE = 'increase';

    public const DIRECTION_DECREASE = 'decrease';

    public const METHOD_NOT_ERROR = 'not_error';

    public const METHOD_CURRENT_RETURN = 'current_return';

    public const METHOD_SEPARATE_NOTIFICATION = 'separate_notification';

    protected $fillable = [
        'business_id', 'number', 'status', 'adjustment_type', 'vat_side', 'direction',
        'correction_method', 'tax_code_id', 'control_ledger_account_id',
        'contra_ledger_account_id', 'effective_date', 'discovered_on',
        'original_period_from', 'original_period_to', 'amount_gbp', 'is_deliberate',
        'reason', 'hmrc_reference', 'created_by', 'approved_by', 'approved_at',
        'posted_by', 'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'discovered_on' => 'date',
            'original_period_from' => 'date',
            'original_period_to' => 'date',
            'amount_gbp' => 'decimal:2',
            'is_deliberate' => 'boolean',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VatAdjustment $adjustment): void {
            if (blank($adjustment->status)) {
                $adjustment->status = self::STATUS_DRAFT;
            }

            if ($adjustment->status !== self::STATUS_DRAFT) {
                throw new DomainException('A VAT adjustment must be created as a draft.');
            }
        });

        static::updating(function (VatAdjustment $adjustment): void {
            if ($adjustment->getOriginal('status') !== self::STATUS_DRAFT && $adjustment->isDirty(self::accountingFields())) {
                throw new DomainException('An approved VAT adjustment is immutable.');
            }
        });

        static::deleting(function (): void {
            throw new DomainException('A VAT adjustment is an audit record and cannot be deleted.');
        });
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_POSTED];
    }

    /** @return list<string> */
    public static function types(): array
    {
        return [self::TYPE_ACCOUNTING_ADJUSTMENT, self::TYPE_ERROR_CORRECTION];
    }

    /** @return list<string> */
    public static function sides(): array
    {
        return [self::SIDE_OUTPUT, self::SIDE_INPUT];
    }

    /** @return list<string> */
    public static function directions(): array
    {
        return [self::DIRECTION_INCREASE, self::DIRECTION_DECREASE];
    }

    /** @return list<string> */
    public static function correctionMethods(): array
    {
        return [self::METHOD_NOT_ERROR, self::METHOD_CURRENT_RETURN, self::METHOD_SEPARATE_NOTIFICATION];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function controlLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'control_ledger_account_id');
    }

    public function contraLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'contra_ledger_account_id');
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    public function journalEntries(): MorphMany
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    /** @return list<string> */
    private static function accountingFields(): array
    {
        return [
            'business_id', 'number', 'adjustment_type', 'vat_side', 'direction',
            'correction_method', 'tax_code_id', 'control_ledger_account_id',
            'contra_ledger_account_id', 'effective_date', 'discovered_on',
            'original_period_from', 'original_period_to', 'amount_gbp',
            'is_deliberate', 'reason', 'hmrc_reference', 'created_by',
        ];
    }
}
