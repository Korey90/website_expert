<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditCalculationInvalidator;
use App\Services\Finance\UniversalCreditFxSnapshotCalculator;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class SetUniversalCreditGbpSnapshotAction
{
    public function __construct(
        private readonly UniversalCreditFxSnapshotCalculator $calculator,
        private readonly UniversalCreditCalculationInvalidator $invalidator,
    ) {}

    public function execute(
        UniversalCreditReportItem $item,
        User $actor,
        int|float|string $gbpExchangeRate,
        string $exchangeRateSource,
        string $exchangeRateDate,
        string $reason,
    ): UniversalCreditReportItem {
        $this->authorize($item, $actor);
        $source = trim($exchangeRateSource);
        $reason = trim($reason);
        $date = $this->date($exchangeRateDate);

        if (mb_strlen($source) < 2 || mb_strlen($source) > 100) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
        }

        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_reason'));
        }

        return DB::transaction(function () use ($item, $actor, $gbpExchangeRate, $source, $date, $reason): UniversalCreditReportItem {
            $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $item->business_id)
                ->whereKey($item->assessment_period_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked = UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->where('assessment_period_id', $period->id)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            if (! $locked->source_is_current) {
                throw new DomainException(__('finance.universal_credit.errors.source_not_current'));
            }

            if ($locked->original_currency === 'GBP') {
                throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
            }

            $snapshot = $this->calculator->calculate((string) $locked->original_amount, $gbpExchangeRate);

            $locked->update([
                'gross_amount_pence' => $snapshot['amount_pence'],
                'gbp_exchange_rate' => $snapshot['rate'],
                'exchange_rate_source' => $source,
                'exchange_rate_date' => $date->toDateString(),
                'fx_snapshot_reason' => $reason,
                'vat_amount_pence' => null,
                'vat_snapshot_source' => null,
                'vat_snapshot_reason' => null,
                'business_share_pence' => null,
                'reportable_amount_pence' => null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);
            $this->invalidator->invalidate($period);

            return $locked->fresh();
        }, 3);
    }

    private function date(string $value): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

            if ($date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('Non-canonical date.');
            }

            return $date;
        } catch (\Throwable $exception) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'), previous: $exception);
        }
    }

    private function authorize(UniversalCreditReportItem $item, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $item->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
