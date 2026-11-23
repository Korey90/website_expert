<?php

namespace App\Services\Finance;

use App\Data\Finance\UniversalCreditPreflightResult;
use App\Models\BankTransaction;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditProfile;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use Illuminate\Support\Collection;

class UniversalCreditPreflightService
{
    public function __construct(
        private readonly UniversalCreditPolicyHashService $policyHasher,
        private readonly UniversalCreditAssessmentPeriodSnapshotService $snapshotService,
        private readonly UniversalCreditSourceCollector $sourceCollector,
        private readonly UniversalCreditEvidenceService $evidence,
        private readonly UniversalCreditDoubleClaimGuard $doubleClaimGuard,
    ) {}

    public function inspect(UniversalCreditAssessmentPeriod $period): UniversalCreditPreflightResult
    {
        $errors = [];
        $warnings = [];
        $items = UniversalCreditReportItem::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->orderBy('cash_date')->orderBy('id')->get();
        $simplified = UniversalCreditSimplifiedExpense::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->orderBy('id')->get();

        if (! is_array($period->policy_rules_snapshot)
            || ! hash_equals((string) $period->policy_rules_hash, $this->policyHasher->hash($period->policy_rules_snapshot))) {
            $this->add($errors, 'policy_integrity');
        }

        foreach ($items as $item) {
            $context = ['item_id' => $item->id];

            if ($item->classification_status === UniversalCreditReportItem::STATUS_REVIEW_REQUIRED) {
                $this->add($errors, 'classification_required', $context);
            }

            if (! $item->source_is_current && $item->classification_status !== UniversalCreditReportItem::STATUS_EXCLUDED) {
                $this->add($errors, 'stale_source_included', $context);
            }

            if ($item->classification_status === UniversalCreditReportItem::STATUS_INCLUDED) {
                if ($item->gross_amount_pence === null || $item->business_share_pence === null || $item->reportable_amount_pence === null) {
                    $this->add($errors, 'calculation_incomplete', $context);
                }

                if ($period->vat_reporting_method_snapshot === UniversalCreditProfile::VAT_EXCLUSIVE
                    && $item->vat_amount_pence === null) {
                    $this->add($errors, 'vat_snapshot_required', $context);
                }

                $evidence = $this->evidence->forItem($item);
                if ($evidence['state'] === 'attachment_not_clean') {
                    $this->add($errors, 'evidence_not_clean', $context);
                } elseif (in_array($evidence['state'], ['missing', 'missing_with_reason', 'source_not_retained'], true)) {
                    $this->add($warnings, 'evidence_missing', [...$context, 'state' => $evidence['state']]);
                }

                if ($item->source_type === (new BankTransaction)->getMorphClass()) {
                    $transaction = BankTransaction::withoutGlobalScopes()
                        ->where('business_id', $period->business_id)->find($item->source_id);
                    if ($transaction?->reconciliation_status !== BankTransaction::STATUS_RECONCILED) {
                        $this->add($warnings, 'bank_transaction_unreconciled', $context);
                    }
                }
            }
        }

        foreach ($simplified->where('status', UniversalCreditSimplifiedExpense::STATUS_ACTIVE) as $expense) {
            $context = ['simplified_expense_id' => $expense->id];
            $evidence = $this->evidence->forSimplified($expense);

            if ($expense->policy_version_id !== $period->policy_version_id
                || ! hash_equals((string) $expense->policy_rules_hash, (string) $period->policy_rules_hash)) {
                $this->add($errors, 'policy_integrity', $context);
            }

            if ($evidence['state'] === 'attachment_not_clean') {
                $this->add($errors, 'evidence_not_clean', $context);
            } elseif (in_array($evidence['state'], ['missing', 'missing_with_reason'], true)) {
                $this->add($warnings, 'evidence_missing', [...$context, 'state' => $evidence['state']]);
            }
        }

        $this->inspectSources($period, $items, $errors);

        try {
            $this->doubleClaimGuard->assertPeriod($period);
        } catch (\DomainException) {
            $this->add($errors, 'simplified_double_claim');
        }

        $income = $items->where('classification_status', UniversalCreditReportItem::STATUS_INCLUDED)
            ->where('direction', UniversalCreditReportItem::DIRECTION_INCOME)->sum('reportable_amount_pence');
        $expense = $items->where('classification_status', UniversalCreditReportItem::STATUS_INCLUDED)
            ->where('direction', UniversalCreditReportItem::DIRECTION_EXPENSE)->sum('reportable_amount_pence')
            + $simplified->where('status', UniversalCreditSimplifiedExpense::STATUS_ACTIVE)->sum('calculated_amount_pence');

        if ($income !== $period->income_total_pence
            || $expense !== $period->expense_total_pence
            || $income - $expense !== $period->net_total_pence) {
            $this->add($errors, 'totals_mismatch');
        }

        $calculationHash = $this->snapshotService->hash($period, $items, $simplified);
        if (blank($period->source_snapshot_hash)
            || ! hash_equals((string) $period->source_snapshot_hash, $calculationHash)) {
            $this->add($errors, 'calculation_snapshot_mismatch');
        }

        return new UniversalCreditPreflightResult($errors, $warnings);
    }

    /**
     * @param  Collection<int, UniversalCreditReportItem>  $items
     * @param  list<array{code: string, context: array<string, mixed>}>  $errors
     */
    private function inspectSources(UniversalCreditAssessmentPeriod $period, $items, array &$errors): void
    {
        $manualType = (new UniversalCreditManualEntry)->getMorphClass();
        $candidates = collect($this->sourceCollector->collect($period))
            ->keyBy(fn ($candidate): string => $candidate->identityHash($period));
        $stored = $items->where('source_type', '!=', $manualType)->keyBy('source_identity_hash');

        foreach ($candidates as $identity => $candidate) {
            $item = $stored->get($identity);
            if (! $item) {
                $this->add($errors, 'source_not_collected', ['source_identity_hash' => $identity]);
            } elseif (! hash_equals((string) $item->source_fingerprint, $candidate->fingerprint())) {
                $this->add($errors, 'source_changed', ['item_id' => $item->id]);
            }
        }

        foreach ($stored->where('source_is_current', true) as $item) {
            if (! $candidates->has($item->source_identity_hash)) {
                $this->add($errors, 'source_no_longer_current', ['item_id' => $item->id]);
            }
        }

        foreach ($items->where('source_type', $manualType) as $item) {
            $entry = UniversalCreditManualEntry::withoutGlobalScopes()
                ->where('business_id', $period->business_id)->find($item->source_id);
            if (! $entry || ($entry->status === UniversalCreditManualEntry::STATUS_VOID
                    && $item->classification_status !== UniversalCreditReportItem::STATUS_EXCLUDED)) {
                $this->add($errors, 'source_no_longer_current', ['item_id' => $item->id]);
            }
        }
    }

    /**
     * @param  list<array{code: string, context: array<string, mixed>}>  $issues
     * @param  array<string, mixed>  $context
     */
    private function add(array &$issues, string $code, array $context = []): void
    {
        $issues[] = ['code' => $code, 'context' => $context];
    }
}
