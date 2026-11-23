<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditSimplifiedExpense;
use App\Models\User;
use App\Services\Finance\UniversalCreditCalculationInvalidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidUniversalCreditSimplifiedExpenseAction
{
    public function __construct(private readonly UniversalCreditCalculationInvalidator $invalidator) {}

    public function execute(
        UniversalCreditSimplifiedExpense $expense,
        User $actor,
        string $reason,
    ): UniversalCreditSimplifiedExpense {
        $this->authorize($expense, $actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_void_reason'));
        }

        return DB::transaction(function () use ($expense, $actor, $reason): UniversalCreditSimplifiedExpense {
            $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $expense->business_id)
                ->whereKey($expense->assessment_period_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked = UniversalCreditSimplifiedExpense::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->where('assessment_period_id', $period->id)
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            if ($locked->status === UniversalCreditSimplifiedExpense::STATUS_VOID) {
                return $locked;
            }

            $locked->update([
                'status' => UniversalCreditSimplifiedExpense::STATUS_VOID,
                'voided_by' => $actor->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
            $this->invalidator->invalidate($period);

            return $locked->fresh();
        }, 3);
    }

    private function authorize(UniversalCreditSimplifiedExpense $expense, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $expense->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
