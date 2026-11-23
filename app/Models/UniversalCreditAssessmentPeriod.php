<?php

namespace App\Models;

use App\Services\Finance\UniversalCreditPolicyHashService;
use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversalCreditAssessmentPeriod extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_REPORTED = 'reported';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'business_id', 'universal_credit_profile_id', 'policy_version_id', 'number',
        'date_from', 'date_to', 'status', 'vat_reporting_method_snapshot',
        'policy_rules_snapshot', 'policy_rules_hash', 'income_total_pence',
        'expense_total_pence', 'net_total_pence', 'source_snapshot_hash',
        'review_snapshot_version', 'review_snapshot', 'review_snapshot_hash',
        'preflight_warning_count', 'review_confirmation_version',
        'created_by', 'reviewed_by', 'reviewed_at', 'review_notes', 'locked_by',
        'locked_at', 'lock_reason', 'reported_by', 'reported_at',
        'report_confirmation_reference', 'superseded_by_user_id',
        'superseding_period_id', 'superseded_at', 'supersede_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'policy_rules_snapshot' => 'array',
            'income_total_pence' => 'integer',
            'expense_total_pence' => 'integer',
            'net_total_pence' => 'integer',
            'review_snapshot_version' => 'integer',
            'review_snapshot' => 'array',
            'preflight_warning_count' => 'integer',
            'reviewed_at' => 'datetime',
            'locked_at' => 'datetime',
            'reported_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $period): void {
            $period->status ??= self::STATUS_DRAFT;

            if ($period->status !== self::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_created_draft'));
            }

            if (! is_array($period->policy_rules_snapshot)
                || ! hash_equals(
                    (string) $period->policy_rules_hash,
                    app(UniversalCreditPolicyHashService::class)->hash($period->policy_rules_snapshot),
                )) {
                throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
            }

            if ($period->vat_reporting_method_snapshot !== null
                && ! in_array($period->vat_reporting_method_snapshot, UniversalCreditProfile::vatReportingMethods(), true)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_vat_method'));
            }

            if ($period->date_from === null
                || $period->date_to === null
                || $period->date_to->lt($period->date_from)
                || $period->date_from->diffInDays($period->date_to) + 1 > (int) config('finance.universal_credit.max_period_days', 31)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_period_dates'));
            }
        });

        static::updating(function (self $period): void {
            if ($period->isDirty([
                'business_id', 'universal_credit_profile_id', 'policy_version_id', 'number',
                'date_from', 'date_to', 'vat_reporting_method_snapshot',
                'policy_rules_snapshot', 'policy_rules_hash', 'created_by',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.period_foundation_immutable'));
            }

            $originalStatus = (string) $period->getOriginal('status');

            if ($originalStatus !== self::STATUS_DRAFT && $period->isDirty([
                'income_total_pence', 'expense_total_pence', 'net_total_pence', 'source_snapshot_hash',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.period_calculation_immutable'));
            }

            if ($period->isDirty([
                'review_snapshot_version', 'review_snapshot', 'review_snapshot_hash',
                'preflight_warning_count', 'review_confirmation_version',
            ]) && ! ($originalStatus === self::STATUS_DRAFT
                && $period->isDirty('status')
                && $period->status === self::STATUS_IN_REVIEW)) {
                throw new DomainException(__('finance.universal_credit.errors.review_snapshot_immutable'));
            }

            if ($period->isDirty(['reviewed_by', 'reviewed_at', 'review_notes'])
                && ! ($originalStatus === self::STATUS_DRAFT
                    && $period->isDirty('status')
                    && $period->status === self::STATUS_IN_REVIEW)) {
                throw new DomainException(__('finance.universal_credit.errors.review_metadata_immutable'));
            }

            if ($period->isDirty(['locked_by', 'locked_at', 'lock_reason'])
                && ! ($originalStatus === self::STATUS_IN_REVIEW
                    && $period->isDirty('status')
                    && $period->status === self::STATUS_LOCKED)) {
                throw new DomainException(__('finance.universal_credit.errors.lock_metadata_immutable'));
            }

            if ($period->isDirty(['superseded_by_user_id', 'superseding_period_id', 'superseded_at', 'supersede_reason'])
                && ! ($period->isDirty('status') && $period->status === self::STATUS_SUPERSEDED)) {
                throw new DomainException(__('finance.universal_credit.errors.supersede_metadata_immutable'));
            }

            if ($period->isDirty('status')) {
                $from = $originalStatus;
                $to = (string) $period->status;
                $allowed = match ($from) {
                    self::STATUS_DRAFT => [self::STATUS_IN_REVIEW, self::STATUS_SUPERSEDED],
                    self::STATUS_IN_REVIEW => [self::STATUS_LOCKED, self::STATUS_SUPERSEDED],
                    self::STATUS_LOCKED => [self::STATUS_REPORTED, self::STATUS_SUPERSEDED],
                    self::STATUS_REPORTED => [self::STATUS_SUPERSEDED],
                    default => [],
                };

                if (! in_array($to, $allowed, true)) {
                    throw new DomainException(__('finance.universal_credit.errors.period_invalid_transition', [
                        'from' => $from,
                        'to' => $to,
                    ]));
                }

                if ($to === self::STATUS_IN_REVIEW
                    && ($period->reviewed_by === null
                        || $period->reviewed_at === null
                        || $period->review_snapshot_version === null
                        || ! is_array($period->review_snapshot)
                        || preg_match('/^[a-f0-9]{64}$/', (string) $period->review_snapshot_hash) !== 1
                        || blank($period->review_confirmation_version))) {
                    throw new DomainException(__('finance.universal_credit.errors.invalid_review_transition'));
                }

                if ($to === self::STATUS_LOCKED
                    && ($period->locked_by === null || $period->locked_at === null || blank($period->lock_reason))) {
                    throw new DomainException(__('finance.universal_credit.errors.invalid_lock_transition'));
                }

                if ($to === self::STATUS_SUPERSEDED
                    && ($period->superseded_by_user_id === null
                        || $period->superseded_at === null
                        || blank($period->supersede_reason))) {
                    throw new DomainException(__('finance.universal_credit.errors.invalid_supersede_transition'));
                }
            }
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.errors.period_cannot_delete'));
        });
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_IN_REVIEW,
            self::STATUS_LOCKED,
            self::STATUS_REPORTED,
            self::STATUS_SUPERSEDED,
        ];
    }

    /** @return list<string> */
    public static function activeStatuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_IN_REVIEW, self::STATUS_LOCKED, self::STATUS_REPORTED];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditProfile::class, 'universal_credit_profile_id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditPolicyVersion::class, 'policy_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_by_user_id');
    }

    public function supersedingPeriod(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseding_period_id');
    }

    public function reportItems(): HasMany
    {
        return $this->hasMany(UniversalCreditReportItem::class, 'assessment_period_id');
    }

    public function manualEntries(): HasMany
    {
        return $this->hasMany(UniversalCreditManualEntry::class, 'assessment_period_id');
    }

    public function simplifiedExpenses(): HasMany
    {
        return $this->hasMany(UniversalCreditSimplifiedExpense::class, 'assessment_period_id');
    }
}
