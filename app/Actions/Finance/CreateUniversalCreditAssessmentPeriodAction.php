<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditProfile;
use App\Models\User;
use App\Services\Finance\UniversalCreditPolicyHashService;
use App\Services\Finance\UniversalCreditPolicyResolver;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateUniversalCreditAssessmentPeriodAction
{
    public function __construct(
        private readonly UniversalCreditPolicyResolver $policyResolver,
        private readonly UniversalCreditPolicyHashService $policyHasher,
    ) {}

    public function execute(
        Business $business,
        User $actor,
        string $dateFrom,
        string $dateTo,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($business, $actor);

        try {
            $from = CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom);
            $to = CarbonImmutable::createFromFormat('!Y-m-d', $dateTo);

            if ($from->format('Y-m-d') !== $dateFrom || $to->format('Y-m-d') !== $dateTo) {
                throw new \InvalidArgumentException('Non-canonical date.');
            }
        } catch (\Throwable $exception) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_period_dates'), previous: $exception);
        }

        if ($to->isBefore($from)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_period_dates'));
        }

        $days = $from->diffInDays($to) + 1;

        if ($days > (int) config('finance.universal_credit.max_period_days', 31)) {
            throw new DomainException(__('finance.universal_credit.errors.period_too_long'));
        }

        return DB::transaction(function () use ($business, $actor, $from, $to): UniversalCreditAssessmentPeriod {
            $accountingProfile = AccountingProfile::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->firstOrFail();
            $profile = UniversalCreditProfile::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->first();

            if ($accountingProfile->entity_type !== 'sole_trader') {
                throw new DomainException(__('finance.universal_credit.errors.sole_trader_only'));
            }

            if (! $profile?->is_enabled) {
                throw new DomainException(__('finance.universal_credit.errors.profile_disabled'));
            }

            $overlaps = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereIn('status', UniversalCreditAssessmentPeriod::activeStatuses())
                ->whereDate('date_from', '<=', $to->toDateString())
                ->whereDate('date_to', '>=', $from->toDateString())
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw new DomainException(__('finance.universal_credit.errors.period_overlap'));
            }

            $policy = $this->policyResolver->forPeriod($from, $to);
            $rules = $policy->rules;

            if (! is_array($rules)) {
                throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
            }

            $vatMethod = $this->vatMethodForPeriod($accountingProfile, $profile, $from, $to);
            $version = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereDate('date_from', $from->toDateString())
                ->whereDate('date_to', $to->toDateString())
                ->count() + 1;

            return UniversalCreditAssessmentPeriod::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'universal_credit_profile_id' => $profile->id,
                'policy_version_id' => $policy->id,
                'number' => 'UC-'.$from->format('Ymd').'-'.$to->format('Ymd').'-V'.$version,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'status' => UniversalCreditAssessmentPeriod::STATUS_DRAFT,
                'vat_reporting_method_snapshot' => $vatMethod,
                'policy_rules_snapshot' => $rules,
                'policy_rules_hash' => $this->policyHasher->hash($rules),
                'income_total_pence' => 0,
                'expense_total_pence' => 0,
                'net_total_pence' => 0,
                'created_by' => $actor->id,
            ]);
        });
    }

    private function vatMethodForPeriod(
        AccountingProfile $accountingProfile,
        UniversalCreditProfile $profile,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): ?string {
        $vatApplies = $accountingProfile->vat_registered
            && ($accountingProfile->vat_effective_from === null || $accountingProfile->vat_effective_from->lte($to))
            && ($accountingProfile->vat_effective_to === null || $accountingProfile->vat_effective_to->gte($from));

        if (! $vatApplies) {
            return null;
        }

        $methodRequiredFrom = $from;

        if ($accountingProfile->vat_effective_from !== null
            && $accountingProfile->vat_effective_from->gt($methodRequiredFrom)) {
            $methodRequiredFrom = $accountingProfile->vat_effective_from->toImmutable();
        }

        if (! in_array($profile->vat_reporting_method, UniversalCreditProfile::vatReportingMethods(), true)
            || $profile->vat_method_effective_from === null
            || $profile->vat_method_effective_from->gt($methodRequiredFrom)) {
            throw new DomainException(__('finance.universal_credit.errors.vat_method_not_effective'));
        }

        return $profile->vat_reporting_method;
    }

    private function authorize(Business $business, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
