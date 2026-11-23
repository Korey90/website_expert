<?php

namespace App\Models;

use App\Services\Finance\UniversalCreditPolicyHashService;
use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UniversalCreditSimplifiedExpense extends Model
{
    use BelongsToTenant, HasFactory;

    public const TYPE_VEHICLE_MILEAGE = 'vehicle_mileage';

    public const TYPE_HOME_WORKING = 'home_working';

    public const TYPE_BUSINESS_PREMISES_LIVING = 'business_premises_living';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'business_id', 'assessment_period_id', 'policy_version_id', 'type',
        'inputs', 'calculated_amount_pence', 'policy_rules_hash', 'description',
        'evidence_missing_reason', 'status', 'created_by', 'voided_by',
        'calculated_at', 'voided_at', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'calculated_amount_pence' => 'integer',
            'calculated_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $expense): void {
            $expense->status ??= self::STATUS_ACTIVE;

            if ($expense->status !== self::STATUS_ACTIVE
                || ! in_array($expense->type, self::types(), true)
                || $expense->calculated_amount_pence < 0) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_expense'));
            }

            $expense->assertDraftPeriodAndPolicy();
        });

        static::updating(function (self $expense): void {
            if ($expense->isDirty([
                'business_id', 'assessment_period_id', 'policy_version_id', 'type',
                'inputs', 'calculated_amount_pence', 'policy_rules_hash',
                'description', 'evidence_missing_reason', 'created_by', 'calculated_at',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.simplified_expense_immutable'));
            }

            if ($expense->isDirty(['voided_by', 'voided_at', 'void_reason'])
                && ! $expense->isDirty('status')) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_expense'));
            }

            if ($expense->isDirty('status')
                && ($expense->getOriginal('status') !== self::STATUS_ACTIVE
                    || $expense->status !== self::STATUS_VOID
                    || $expense->voided_by === null
                    || $expense->voided_at === null
                    || blank($expense->void_reason))) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_expense'));
            }

            $expense->assertDraftPeriodAndPolicy();
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.errors.simplified_expense_cannot_delete'));
        });
    }

    /** @return list<string> */
    public static function types(): array
    {
        return [
            self::TYPE_VEHICLE_MILEAGE,
            self::TYPE_HOME_WORKING,
            self::TYPE_BUSINESS_PREMISES_LIVING,
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

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditPolicyVersion::class, 'policy_version_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }

    private function assertDraftPeriodAndPolicy(): void
    {
        $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()->find($this->assessment_period_id);

        if (! $period
            || $period->business_id !== $this->business_id
            || $period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
            throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
        }

        if ($period->policy_version_id !== $this->policy_version_id
            || ! hash_equals((string) $period->policy_rules_hash, (string) $this->policy_rules_hash)
            || ! hash_equals(
                (string) $this->policy_rules_hash,
                app(UniversalCreditPolicyHashService::class)->hash($period->policy_rules_snapshot),
            )) {
            throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
        }
    }
}
