<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditCalculationInvalidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidUniversalCreditManualEntryAction
{
    public function __construct(private readonly UniversalCreditCalculationInvalidator $invalidator) {}

    public function execute(
        UniversalCreditManualEntry $entry,
        User $actor,
        string $reason,
    ): UniversalCreditManualEntry {
        $this->authorize($entry, $actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_void_reason'));
        }

        return DB::transaction(function () use ($entry, $actor, $reason): UniversalCreditManualEntry {
            $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $entry->business_id)
                ->whereKey($entry->assessment_period_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked = UniversalCreditManualEntry::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->where('assessment_period_id', $period->id)
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            if ($locked->status === UniversalCreditManualEntry::STATUS_VOID) {
                return $locked->fresh(['reportItems']);
            }

            $locked->update([
                'status' => UniversalCreditManualEntry::STATUS_VOID,
                'voided_by' => $actor->id,
                'voided_at' => now(),
                'void_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->assessment_period_id)
                ->where('source_type', $locked->getMorphClass())
                ->where('source_id', $locked->id)
                ->get()
                ->each->update([
                    'classification_status' => UniversalCreditReportItem::STATUS_EXCLUDED,
                    'business_use_basis_points' => 0,
                    'business_use_reason' => null,
                    'classification_reason' => $reason,
                    'business_share_pence' => null,
                    'reportable_amount_pence' => null,
                    'source_is_current' => false,
                    'source_change_reason' => 'manual_entry_voided',
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                ]);
            $this->invalidator->invalidate($period);

            return $locked->fresh(['reportItems']);
        }, 3);
    }

    private function authorize(UniversalCreditManualEntry $entry, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $entry->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
