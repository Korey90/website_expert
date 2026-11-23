<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditProfile;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use App\Models\User;
use App\Services\Finance\UniversalCreditAssessmentPeriodSnapshotService;
use App\Services\Finance\UniversalCreditDoubleClaimGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalculateUniversalCreditAssessmentPeriodAction
{
    public function __construct(
        private readonly UniversalCreditDoubleClaimGuard $doubleClaimGuard,
        private readonly UniversalCreditAssessmentPeriodSnapshotService $snapshotService,
    ) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($period, $actor);

        return DB::transaction(function () use ($period, $actor): UniversalCreditAssessmentPeriod {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            $items = UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->orderBy('cash_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $simplified = UniversalCreditSimplifiedExpense::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($items->contains(fn (UniversalCreditReportItem $item): bool => $item->classification_status === UniversalCreditReportItem::STATUS_REVIEW_REQUIRED
                || (! $item->source_is_current && $item->classification_status !== UniversalCreditReportItem::STATUS_EXCLUDED))) {
                throw new DomainException(__('finance.universal_credit.errors.calculation_incomplete'));
            }

            $this->doubleClaimGuard->assertPeriod($locked);
            $prepared = $this->prepareItems($locked, $items);
            $incomeTotal = 0;
            $expenseTotal = 0;

            foreach ($prepared as $row) {
                /** @var UniversalCreditReportItem $item */
                $item = $row['item'];
                $item->update([
                    'business_share_pence' => $row['business_share_pence'],
                    'reportable_amount_pence' => $row['reportable_amount_pence'],
                ]);

                if ($item->classification_status !== UniversalCreditReportItem::STATUS_INCLUDED) {
                    continue;
                }

                if ($item->direction === UniversalCreditReportItem::DIRECTION_INCOME) {
                    $incomeTotal += $row['reportable_amount_pence'];
                } else {
                    $expenseTotal += $row['reportable_amount_pence'];
                }
            }

            foreach ($simplified as $expense) {
                if ($expense->status !== UniversalCreditSimplifiedExpense::STATUS_ACTIVE) {
                    continue;
                }

                if ($expense->policy_version_id !== $locked->policy_version_id
                    || ! hash_equals((string) $expense->policy_rules_hash, (string) $locked->policy_rules_hash)) {
                    throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
                }

                $expenseTotal += $expense->calculated_amount_pence;
            }

            $calculatedItems = UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->orderBy('cash_date')
                ->orderBy('id')
                ->get();
            $hash = $this->snapshotService->hash($locked, $calculatedItems, $simplified);
            $locked->update([
                'income_total_pence' => $incomeTotal,
                'expense_total_pence' => $expenseTotal,
                'net_total_pence' => $incomeTotal - $expenseTotal,
                'source_snapshot_hash' => $hash,
            ]);

            return $locked->fresh(['reportItems', 'simplifiedExpenses']);
        }, 3);
    }

    /**
     * @param  Collection<int, UniversalCreditReportItem>  $items
     * @return list<array{item: UniversalCreditReportItem, business_share_pence: int, reportable_amount_pence: int}>
     */
    private function prepareItems(UniversalCreditAssessmentPeriod $period, Collection $items): array
    {
        $loanLimit = (int) data_get($period->policy_rules_snapshot, 'business_loan_interest.monthly_limit_pence', 0);

        if ($loanLimit <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
        }

        $loanRemaining = $loanLimit;
        $prepared = [];

        foreach ($items as $item) {
            if ($item->classification_status === UniversalCreditReportItem::STATUS_EXCLUDED) {
                $prepared[] = [
                    'item' => $item,
                    'business_share_pence' => 0,
                    'reportable_amount_pence' => 0,
                ];

                continue;
            }

            if ($item->gross_amount_pence === null) {
                throw new DomainException(__('finance.universal_credit.errors.gbp_snapshot_required'));
            }

            $base = $item->gross_amount_pence;

            if ($period->vat_reporting_method_snapshot === UniversalCreditProfile::VAT_EXCLUSIVE) {
                if ($item->vat_amount_pence === null) {
                    throw new DomainException(__('finance.universal_credit.errors.vat_snapshot_required'));
                }

                if ($item->vat_amount_pence < 0 || $item->vat_amount_pence > $base) {
                    throw new DomainException(__('finance.universal_credit.errors.invalid_vat_snapshot'));
                }

                $base -= $item->vat_amount_pence;
            }

            $businessShare = $this->roundDivide($base * $item->business_use_basis_points, 10000);
            $reportable = $businessShare;

            if ($item->calculation_rule === UniversalCreditReportItem::RULE_BUSINESS_LOAN_INTEREST) {
                $reportable = min($businessShare, $loanRemaining);
                $loanRemaining -= $reportable;
            }

            $prepared[] = [
                'item' => $item,
                'business_share_pence' => $businessShare,
                'reportable_amount_pence' => $reportable,
            ];
        }

        return $prepared;
    }

    private function roundDivide(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
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
