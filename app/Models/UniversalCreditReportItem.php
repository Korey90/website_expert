<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UniversalCreditReportItem extends Model
{
    use BelongsToTenant, HasFactory;

    public const DIRECTION_INCOME = 'income';

    public const DIRECTION_EXPENSE = 'expense';

    public const STATUS_REVIEW_REQUIRED = 'review_required';

    public const STATUS_INCLUDED = 'included';

    public const STATUS_EXCLUDED = 'excluded';

    public const METHOD_ACTUAL = 'actual';

    public const METHOD_BUSINESS_USE = 'business_use_percentage';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'business_id', 'assessment_period_id', 'source_type', 'source_id',
        'source_event_key', 'source_identity_hash', 'source_fingerprint',
        'direction', 'category', 'classification_status', 'cash_date',
        'document_date', 'original_amount', 'original_currency',
        'gross_amount_pence', 'vat_amount_pence', 'business_use_basis_points',
        'business_use_reason', 'classification_reason', 'calculation_method',
        'business_share_pence', 'reportable_amount_pence', 'source_context',
        'source_is_current', 'source_change_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'cash_date' => 'date',
            'document_date' => 'date',
            'original_amount' => 'decimal:2',
            'gross_amount_pence' => 'integer',
            'vat_amount_pence' => 'integer',
            'business_use_basis_points' => 'integer',
            'business_share_pence' => 'integer',
            'reportable_amount_pence' => 'integer',
            'source_context' => 'array',
            'source_is_current' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            $expected = self::identityHash(
                (string) $item->business_id,
                (int) $item->assessment_period_id,
                (string) $item->source_type,
                (int) $item->source_id,
                (string) $item->source_event_key,
            );

            if (! hash_equals((string) $item->source_identity_hash, $expected)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_report_item'));
            }
        });

        static::updating(function (self $item): void {
            if ($item->isDirty([
                'business_id', 'assessment_period_id', 'source_type', 'source_id',
                'source_event_key', 'source_identity_hash',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.report_item_identity_immutable'));
            }
        });

        static::saving(function (self $item): void {
            if (! in_array($item->direction, self::directions(), true)
                || ! in_array($item->classification_status, self::classificationStatuses(), true)
                || ! in_array($item->calculation_method, self::calculationMethods(), true)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_report_item'));
            }

            if ($item->business_use_basis_points < 0 || $item->business_use_basis_points > 10000) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_business_use'));
            }

            if ($item->direction === self::DIRECTION_INCOME
                && $item->classification_status === self::STATUS_INCLUDED
                && $item->business_use_basis_points !== 10000) {
                throw new DomainException(__('finance.universal_credit.errors.income_business_use'));
            }

            if ($item->classification_status === self::STATUS_INCLUDED
                && $item->direction === self::DIRECTION_EXPENSE
                && $item->business_use_basis_points < 10000
                && blank($item->business_use_reason)) {
                throw new DomainException(__('finance.universal_credit.errors.business_use_reason_required'));
            }

            if ($item->classification_status === self::STATUS_EXCLUDED
                && blank($item->classification_reason)) {
                throw new DomainException(__('finance.universal_credit.errors.classification_reason_required'));
            }

            $item->assertDraftPeriod();
        });

        static::deleting(function (self $item): void {
            $item->assertDraftPeriod();
        });
    }

    /** @return list<string> */
    public static function directions(): array
    {
        return [self::DIRECTION_INCOME, self::DIRECTION_EXPENSE];
    }

    /** @return list<string> */
    public static function classificationStatuses(): array
    {
        return [self::STATUS_REVIEW_REQUIRED, self::STATUS_INCLUDED, self::STATUS_EXCLUDED];
    }

    /** @return list<string> */
    public static function calculationMethods(): array
    {
        return [self::METHOD_ACTUAL, self::METHOD_BUSINESS_USE, self::METHOD_MANUAL];
    }

    public static function identityHash(
        string $businessId,
        int $periodId,
        string $sourceType,
        int $sourceId,
        string $eventKey,
    ): string {
        return hash('sha256', implode('|', [
            $businessId,
            (string) $periodId,
            $sourceType,
            (string) $sourceId,
            $eventKey,
        ]));
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assessmentPeriod(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditAssessmentPeriod::class, 'assessment_period_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    private function assertDraftPeriod(): void
    {
        $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
            ->find($this->assessment_period_id);

        if (! $period
            || $period->business_id !== $this->business_id
            || $period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
            throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
        }
    }
}
