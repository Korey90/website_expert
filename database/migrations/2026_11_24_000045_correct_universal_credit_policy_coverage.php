<?php

use App\Services\Finance\UniversalCreditPolicyHashService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODE = 'ni_uc_self_employment';

    private const VERSION = 1;

    private const ORIGINAL_EFFECTIVE_FROM = '2026-11-23';

    private const CORRECTED_EFFECTIVE_FROM = '2026-05-01';

    public function up(): void
    {
        $policy = $this->policy();
        $effectiveFrom = (string) $policy->effective_from;

        if ($effectiveFrom <= self::CORRECTED_EFFECTIVE_FROM) {
            return;
        }

        if ($effectiveFrom !== self::ORIGINAL_EFFECTIVE_FROM) {
            throw new RuntimeException('Unexpected Universal Credit policy effective date.');
        }

        $this->assertPolicyIntegrity($policy);

        if ($this->referencedPeriodCount((int) $policy->id) > 0) {
            throw new RuntimeException('The Universal Credit policy is already referenced and cannot be corrected in place.');
        }

        DB::table('universal_credit_policy_versions')
            ->where('id', $policy->id)
            ->update([
                'effective_from' => self::CORRECTED_EFFECTIVE_FROM,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $policy = $this->policy();
        $effectiveFrom = (string) $policy->effective_from;

        if ($effectiveFrom < self::CORRECTED_EFFECTIVE_FROM
            || $effectiveFrom === self::ORIGINAL_EFFECTIVE_FROM) {
            return;
        }

        if ($effectiveFrom !== self::CORRECTED_EFFECTIVE_FROM) {
            throw new RuntimeException('Unexpected Universal Credit policy effective date.');
        }

        $this->assertPolicyIntegrity($policy);

        $earlierReferenceExists = DB::table('universal_credit_assessment_periods')
            ->where('policy_version_id', $policy->id)
            ->whereDate('date_from', '<', self::ORIGINAL_EFFECTIVE_FROM)
            ->exists();

        if ($earlierReferenceExists) {
            throw new RuntimeException('The Universal Credit policy coverage cannot be reverted while earlier periods reference it.');
        }

        DB::table('universal_credit_policy_versions')
            ->where('id', $policy->id)
            ->update([
                'effective_from' => self::ORIGINAL_EFFECTIVE_FROM,
                'updated_at' => now(),
            ]);
    }

    private function policy(): object
    {
        $policy = DB::table('universal_credit_policy_versions')
            ->where('code', self::CODE)
            ->where('version', self::VERSION)
            ->first();

        if (! $policy) {
            throw new RuntimeException('The expected Universal Credit policy was not found.');
        }

        return $policy;
    }

    private function assertPolicyIntegrity(object $policy): void
    {
        $rules = json_decode((string) $policy->rules, true, flags: JSON_THROW_ON_ERROR);
        $actualHash = app(UniversalCreditPolicyHashService::class)->hash($rules);

        if (! hash_equals((string) $policy->rules_hash, $actualHash)) {
            throw new RuntimeException('The Universal Credit policy integrity check failed.');
        }
    }

    private function referencedPeriodCount(int $policyId): int
    {
        return DB::table('universal_credit_assessment_periods')
            ->where('policy_version_id', $policyId)
            ->count();
    }
};
