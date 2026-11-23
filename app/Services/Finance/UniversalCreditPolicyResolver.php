<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditPolicyVersion;
use Carbon\CarbonInterface;
use DomainException;

class UniversalCreditPolicyResolver
{
    public function __construct(private readonly UniversalCreditPolicyHashService $hasher) {}

    public function forPeriod(CarbonInterface $from, CarbonInterface $to): UniversalCreditPolicyVersion
    {
        $policy = UniversalCreditPolicyVersion::query()
            ->where('code', config('finance.universal_credit.policy_code', 'ni_uc_self_employment'))
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $from->toDateString())
            ->where(function ($query) use ($to): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $to->toDateString());
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('version')
            ->first();

        if (! $policy) {
            throw new DomainException(__('finance.universal_credit.errors.policy_not_found'));
        }

        $rules = $policy->rules;

        if (! is_array($rules) || ! hash_equals($policy->rules_hash, $this->hasher->hash($rules))) {
            throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
        }

        return $policy;
    }
}
