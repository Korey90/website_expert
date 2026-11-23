<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UniversalCreditManualEntry extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_VOID = 'void';

    public const EVENT_CASH_RECEIPT = 'cash_receipt';

    public const EVENT_CASH_PAYMENT = 'cash_payment';

    public const EVENT_PAYMENT_IN_KIND = 'payment_in_kind';

    public const EVENT_TIPS = 'tips';

    public const EVENT_GRANT = 'grant_or_subsidy';

    public const EVENT_TAX_NI_REFUND = 'tax_or_ni_refund';

    public const EVENT_ASSET_TRANSFER = 'asset_transfer_or_sale';

    public const EVENT_TAX_NI_PENSION_VAT_PAYMENT = 'tax_ni_pension_or_vat_payment';

    public const EVENT_ADJUSTMENT = 'adjustment';

    public const EVENT_OTHER = 'other';

    protected $fillable = [
        'business_id', 'assessment_period_id', 'direction', 'event_type',
        'category', 'cash_date', 'original_amount', 'original_currency',
        'amount_gbp_pence', 'description', 'evidence_missing_reason', 'status',
        'created_by', 'updated_by', 'voided_by', 'voided_at', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'cash_date' => 'date',
            'original_amount' => 'decimal:2',
            'amount_gbp_pence' => 'integer',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            $entry->status ??= self::STATUS_ACTIVE;

            if ($entry->status !== self::STATUS_ACTIVE) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_manual_entry'));
            }
        });

        static::updating(function (self $entry): void {
            if ($entry->isDirty([
                'business_id', 'assessment_period_id', 'direction', 'event_type',
                'category', 'cash_date', 'original_amount', 'original_currency',
                'amount_gbp_pence', 'description', 'created_by',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.manual_entry_immutable'));
            }

            if ($entry->isDirty('status')
                && ($entry->getOriginal('status') !== self::STATUS_ACTIVE
                    || $entry->status !== self::STATUS_VOID
                    || $entry->voided_at === null
                    || blank($entry->void_reason))) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_manual_entry'));
            }
        });

        static::saving(function (self $entry): void {
            if (! in_array($entry->direction, UniversalCreditReportItem::directions(), true)
                || ! in_array($entry->event_type, self::eventTypes(), true)
                || ! in_array($entry->status, [self::STATUS_ACTIVE, self::STATUS_VOID], true)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_manual_entry'));
            }

            $entry->assertDraftPeriod();
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.errors.manual_entry_cannot_delete'));
        });
    }

    /** @return list<string> */
    public static function eventTypes(): array
    {
        return [
            self::EVENT_CASH_RECEIPT,
            self::EVENT_CASH_PAYMENT,
            self::EVENT_PAYMENT_IN_KIND,
            self::EVENT_TIPS,
            self::EVENT_GRANT,
            self::EVENT_TAX_NI_REFUND,
            self::EVENT_ASSET_TRANSFER,
            self::EVENT_TAX_NI_PENSION_VAT_PAYMENT,
            self::EVENT_ADJUSTMENT,
            self::EVENT_OTHER,
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assessmentPeriod(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditAssessmentPeriod::class, 'assessment_period_id');
    }

    public function reportItems(): MorphMany
    {
        return $this->morphMany(UniversalCreditReportItem::class, 'source');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    private function assertDraftPeriod(): void
    {
        $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()->find($this->assessment_period_id);

        if (! $period
            || $period->business_id !== $this->business_id
            || $period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
            throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
        }
    }
}
