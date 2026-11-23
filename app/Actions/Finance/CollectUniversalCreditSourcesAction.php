<?php

namespace App\Actions\Finance;

use App\Data\Finance\UniversalCreditCollectionResult;
use App\Data\Finance\UniversalCreditSourceCandidate;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditSourceCollector;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class CollectUniversalCreditSourcesAction
{
    public function __construct(private readonly UniversalCreditSourceCollector $collector) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
    ): UniversalCreditCollectionResult {
        $this->authorize($period, $actor);

        return DB::transaction(function () use ($period, $actor): UniversalCreditCollectionResult {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            $candidates = $this->collector->collect($locked);
            $existing = UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('source_identity_hash');
            $seen = [];
            $created = 0;
            $updated = 0;
            $unchanged = 0;

            foreach ($candidates as $candidate) {
                $identity = $candidate->identityHash($locked);
                $fingerprint = $candidate->fingerprint();
                $seen[$identity] = true;
                /** @var UniversalCreditReportItem|null $item */
                $item = $existing->get($identity);

                if (! $item) {
                    UniversalCreditReportItem::withoutGlobalScopes()->create([
                        ...$this->sourceData($candidate),
                        'business_id' => $locked->business_id,
                        'assessment_period_id' => $locked->id,
                        'source_identity_hash' => $identity,
                        'source_fingerprint' => $fingerprint,
                        'classification_status' => UniversalCreditReportItem::STATUS_REVIEW_REQUIRED,
                        'business_use_basis_points' => 10000,
                        'calculation_method' => UniversalCreditReportItem::METHOD_ACTUAL,
                        'source_is_current' => true,
                    ]);
                    $created++;

                    continue;
                }

                if (hash_equals((string) $item->source_fingerprint, $fingerprint)
                    && $item->source_is_current) {
                    $unchanged++;

                    continue;
                }

                $item->update([
                    ...$this->sourceData($candidate),
                    'source_fingerprint' => $fingerprint,
                    'classification_status' => UniversalCreditReportItem::STATUS_REVIEW_REQUIRED,
                    'business_use_basis_points' => 10000,
                    'business_use_reason' => null,
                    'classification_reason' => null,
                    'calculation_method' => UniversalCreditReportItem::METHOD_ACTUAL,
                    'business_share_pence' => null,
                    'reportable_amount_pence' => null,
                    'source_is_current' => true,
                    'source_change_reason' => 'source_snapshot_changed',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
                $updated++;
            }

            $removed = 0;
            $markedStale = 0;
            $manualMorph = (new UniversalCreditManualEntry)->getMorphClass();

            foreach ($existing as $identity => $item) {
                if ($item->source_type === $manualMorph || isset($seen[$identity])) {
                    continue;
                }

                if ($item->classification_status === UniversalCreditReportItem::STATUS_REVIEW_REQUIRED
                    && $item->reviewed_at === null) {
                    $item->delete();
                    $removed++;

                    continue;
                }

                $item->update([
                    'category' => 'review_required',
                    'classification_status' => UniversalCreditReportItem::STATUS_REVIEW_REQUIRED,
                    'business_use_basis_points' => 10000,
                    'business_use_reason' => null,
                    'classification_reason' => null,
                    'calculation_method' => UniversalCreditReportItem::METHOD_ACTUAL,
                    'business_share_pence' => null,
                    'reportable_amount_pence' => null,
                    'source_is_current' => false,
                    'source_change_reason' => 'source_no_longer_collectable',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
                $markedStale++;
            }

            return new UniversalCreditCollectionResult(
                discovered: count($candidates),
                created: $created,
                updated: $updated,
                unchanged: $unchanged,
                removed: $removed,
                markedStale: $markedStale,
            );
        }, 3);
    }

    /** @return array<string, mixed> */
    private function sourceData(UniversalCreditSourceCandidate $candidate): array
    {
        return [
            'source_type' => $candidate->source->getMorphClass(),
            'source_id' => $candidate->source->getKey(),
            'source_event_key' => $candidate->eventKey,
            'direction' => $candidate->direction,
            'category' => $candidate->category,
            'cash_date' => $candidate->cashDate->toDateString(),
            'document_date' => $candidate->documentDate?->toDateString(),
            'original_amount' => $candidate->originalAmount,
            'original_currency' => $candidate->originalCurrency,
            'gross_amount_pence' => $candidate->grossAmountPence,
            'vat_amount_pence' => null,
            'source_context' => $candidate->context,
        ];
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
