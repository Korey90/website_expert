<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditSimplifiedExpense;
use App\Models\User;
use App\Services\Finance\FinancialAttachmentService;
use App\Services\Finance\UniversalCreditCalculationInvalidator;
use App\Services\Finance\UniversalCreditDoubleClaimGuard;
use App\Services\Finance\UniversalCreditSimplifiedExpenseCalculator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateUniversalCreditSimplifiedExpenseAction
{
    public function __construct(
        private readonly UniversalCreditSimplifiedExpenseCalculator $calculator,
        private readonly UniversalCreditDoubleClaimGuard $doubleClaimGuard,
        private readonly UniversalCreditCalculationInvalidator $invalidator,
        private readonly FinancialAttachmentService $attachments,
    ) {}

    /** @param array<string, mixed> $inputs */
    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        string $type,
        array $inputs,
        ?string $description = null,
        ?string $evidenceMissingReason = null,
        ?UploadedFile $evidence = null,
    ): UniversalCreditSimplifiedExpense {
        $this->authorize($period, $actor);
        $description = $this->optionalText($description);
        $evidenceMissingReason = $this->optionalText($evidenceMissingReason);

        if ($evidence === null && $evidenceMissingReason === null) {
            throw new DomainException(__('finance.universal_credit.errors.simplified_evidence_required'));
        }

        return DB::transaction(function () use (
            $period,
            $actor,
            $type,
            $inputs,
            $description,
            $evidenceMissingReason,
            $evidence,
        ): UniversalCreditSimplifiedExpense {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            $existing = UniversalCreditSimplifiedExpense::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->where('type', $type)
                ->where('status', UniversalCreditSimplifiedExpense::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new DomainException(__('finance.universal_credit.errors.simplified_type_exists'));
            }

            $this->doubleClaimGuard->assertCanCreateSimplified($locked, $type);
            $calculation = $this->calculator->calculate($type, $inputs, $locked->policy_rules_snapshot);
            $expense = UniversalCreditSimplifiedExpense::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'assessment_period_id' => $locked->id,
                'policy_version_id' => $locked->policy_version_id,
                'type' => $type,
                'inputs' => $calculation['inputs'],
                'calculated_amount_pence' => $calculation['amount_pence'],
                'policy_rules_hash' => $locked->policy_rules_hash,
                'description' => $description,
                'evidence_missing_reason' => $evidence === null ? $evidenceMissingReason : null,
                'status' => UniversalCreditSimplifiedExpense::STATUS_ACTIVE,
                'created_by' => $actor->id,
                'calculated_at' => now(),
            ]);

            if ($evidence !== null) {
                $this->attachments->store($expense, $evidence, $actor, 'other');
            }

            $this->invalidator->invalidate($locked);

            return $expense->fresh(['attachments']);
        }, 3);
    }

    private function optionalText(?string $value): ?string
    {
        $value = trim((string) $value);

        if (mb_strlen($value) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.text_too_long'));
        }

        return $value !== '' ? $value : null;
    }

    private function authorize(UniversalCreditAssessmentPeriod $period, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $period->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
