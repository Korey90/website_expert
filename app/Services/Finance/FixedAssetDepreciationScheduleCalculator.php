<?php

namespace App\Services\Finance;

use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetTransaction;
use Carbon\CarbonImmutable;
use DomainException;

class FixedAssetDepreciationScheduleCalculator
{
    public function __construct(private readonly FixedAssetCostBasisService $money) {}

    /** @return list<array<string, mixed>> */
    public function calculate(FixedAsset $asset, CarbonImmutable|string|null $throughDate = null): array
    {
        $asset = FixedAsset::withoutGlobalScopes()->findOrFail($asset->id);

        if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
            throw new DomainException('A depreciation schedule requires an active fixed asset.');
        }

        if ($asset->depreciation_method === FixedAssetCategory::METHOD_NONE) {
            return [];
        }

        if (! $asset->in_service_date || ! $asset->useful_life_months || ! $asset->acquisition_cost_gbp) {
            throw new DomainException('The fixed asset is missing its in-service date, useful life or historical cost.');
        }

        $inService = CarbonImmutable::parse($asset->in_service_date->toDateString());
        $lifeEnd = $inService->addMonthsNoOverflow($asset->useful_life_months)->subDay();
        $scheduleEnd = $throughDate === null
            ? $lifeEnd
            : $this->date($throughDate, 'depreciation schedule end date')->min($lifeEnd);

        if ($scheduleEnd->lt($inService)) {
            return [];
        }

        $components = $this->components($asset, $inService);

        if ($components === []) {
            throw new DomainException('The fixed asset has no posted acquisition cost transaction.');
        }

        $lastPosted = FixedAssetDepreciationEntry::withoutGlobalScopes()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
            ->orderByDesc('period_end')->orderByDesc('id')->first();
        $cursor = $lastPosted
            ? CarbonImmutable::parse($lastPosted->period_end->toDateString())->addDay()
            : $inService;

        if ($cursor->gt($scheduleEnd)) {
            return [];
        }

        $accumulated = $this->money->toMinorUnits($asset->accumulated_depreciation_gbp ?? '0.00', 'accumulated depreciation');
        $rows = [];

        while ($cursor->lte($scheduleEnd)) {
            $periodEnd = $cursor->endOfMonth()->min($scheduleEnd);
            $openingCost = $this->costAt($components, $cursor);
            $closingCost = $this->costAt($components, $periodEnd);
            $residual = $this->percentage($closingCost, (string) $asset->residual_value_percent);
            $openingNbv = max(0, $openingCost - $accumulated);
            $remaining = max(0, $closingCost - $residual - $accumulated);

            if ($remaining === 0) {
                break;
            }

            $amount = match ($asset->depreciation_method) {
                FixedAssetCategory::METHOD_STRAIGHT_LINE => $this->straightLineAmount(
                    $components,
                    $cursor,
                    $periodEnd,
                    $lifeEnd,
                    (string) $asset->residual_value_percent,
                ),
                FixedAssetCategory::METHOD_REDUCING_BALANCE => $this->reducingBalanceAmount(
                    $components,
                    $cursor,
                    $periodEnd,
                    $openingCost,
                    $accumulated,
                    (string) $asset->residual_value_percent,
                    (string) $asset->depreciation_rate_percent,
                    $lifeEnd,
                ),
                default => throw new DomainException('Unsupported fixed asset depreciation method.'),
            };

            $amount = min($remaining, $amount);
            if ($periodEnd->isSameDay($lifeEnd)) {
                $amount = $remaining;
            }

            if ($amount > 0) {
                $closingAccumulated = $accumulated + $amount;
                $rows[] = [
                    'period_start' => $cursor->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'posting_date' => $periodEnd->toDateString(),
                    'method_snapshot' => $asset->depreciation_method,
                    'rate_percent_snapshot' => $asset->depreciation_rate_percent,
                    'useful_life_months_snapshot' => $asset->useful_life_months,
                    'residual_value_gbp' => $this->money->fromMinorUnits($residual),
                    'opening_cost_gbp' => $this->money->fromMinorUnits($openingCost),
                    'opening_accumulated_depreciation_gbp' => $this->money->fromMinorUnits($accumulated),
                    'opening_net_book_value_gbp' => $this->money->fromMinorUnits($openingNbv),
                    'amount_gbp' => $this->money->fromMinorUnits($amount),
                    'closing_accumulated_depreciation_gbp' => $this->money->fromMinorUnits($closingAccumulated),
                    'closing_net_book_value_gbp' => $this->money->fromMinorUnits(max(0, $closingCost - $closingAccumulated)),
                ];
                $accumulated = $closingAccumulated;
            }

            $cursor = $periodEnd->addDay();
        }

        return $rows;
    }

    private function date(CarbonImmutable|string $value, string $label): CarbonImmutable
    {
        if ($value instanceof CarbonImmutable) {
            return $value->startOfDay();
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', trim($value));
        } catch (\Throwable) {
            $date = null;
        }

        if (! $date || $date->format('Y-m-d') !== trim($value)) {
            throw new DomainException("The {$label} must use a valid YYYY-MM-DD value.");
        }

        return $date;
    }

    /** @return list<array{date: CarbonImmutable, amount: int}> */
    private function components(FixedAsset $asset, CarbonImmutable $inService): array
    {
        return FixedAssetTransaction::withoutGlobalScopes()
            ->where('fixed_asset_id', $asset->id)
            ->where('status', FixedAssetTransaction::STATUS_POSTED)
            ->whereIn('type', [FixedAssetTransaction::TYPE_ACQUISITION, FixedAssetTransaction::TYPE_IMPROVEMENT])
            ->orderBy('event_date')->orderBy('id')->get()
            ->map(fn (FixedAssetTransaction $transaction): array => [
                'date' => $transaction->type === FixedAssetTransaction::TYPE_ACQUISITION
                    ? $inService
                    : CarbonImmutable::parse($transaction->event_date->toDateString()),
                'amount' => $this->money->toMinorUnits($transaction->amount_gbp, 'fixed asset cost component'),
            ])->all();
    }

    /** @param list<array{date: CarbonImmutable, amount: int}> $components */
    private function costAt(array $components, CarbonImmutable $date): int
    {
        return array_sum(array_map(
            fn (array $component): int => $component['date']->lte($date) ? $component['amount'] : 0,
            $components,
        ));
    }

    /** @param list<array{date: CarbonImmutable, amount: int}> $components */
    private function straightLineAmount(
        array $components,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        CarbonImmutable $lifeEnd,
        string $residualPercent,
    ): int {
        $amount = 0;

        foreach ($components as $component) {
            $start = $component['date'];
            if ($start->gt($lifeEnd) || $start->gt($periodEnd)) {
                continue;
            }

            $depreciable = $component['amount'] - $this->percentage($component['amount'], $residualPercent);
            $totalDays = $start->diffInDays($lifeEnd) + 1;
            $daysBefore = $periodStart->lte($start) ? 0 : $start->diffInDays($periodStart->subDay()) + 1;
            $through = $periodEnd->min($lifeEnd);
            $daysThrough = $start->diffInDays($through) + 1;
            $amount += $this->roundedRatio($depreciable, $daysThrough, $totalDays)
                - $this->roundedRatio($depreciable, min($daysBefore, $totalDays), $totalDays);
        }

        return max(0, $amount);
    }

    /** @param list<array{date: CarbonImmutable, amount: int}> $components */
    private function reducingBalanceAmount(
        array $components,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $openingCost,
        int $openingAccumulated,
        string $residualPercent,
        string $ratePercent,
        CarbonImmutable $lifeEnd,
    ): int {
        $rateBasisPoints = $this->percentageBasisPoints($ratePercent, true);
        $cost = $openingCost;
        $nbv = max(0, $openingCost - $openingAccumulated);
        $depreciation = 0;
        $carry = 0;
        $date = $periodStart;

        while ($date->lte($periodEnd)) {
            if (! $date->isSameDay($periodStart)) {
                foreach ($components as $component) {
                    if ($component['date']->isSameDay($date)) {
                        $cost += $component['amount'];
                        $nbv += $component['amount'];
                    }
                }
            }

            $residual = $this->percentage($cost, $residualPercent);
            $available = max(0, $nbv - $residual);
            if ($available > 0) {
                $denominator = 10000 * $date->daysInYear;
                $numerator = ($nbv * $rateBasisPoints) + $carry;
                $daily = min($available, intdiv($numerator, $denominator));
                $carry = $numerator % $denominator;
                $nbv -= $daily;
                $depreciation += $daily;
            }

            if ($date->isSameDay($lifeEnd)) {
                $depreciation += max(0, $nbv - $this->percentage($cost, $residualPercent));
                break;
            }

            $date = $date->addDay();
        }

        return $depreciation;
    }

    private function percentage(int $amount, string $percent): int
    {
        return $this->roundedRatio($amount, $this->percentageBasisPoints($percent), 10000);
    }

    private function percentageBasisPoints(string $percent, bool $positive = false): int
    {
        if (! preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', trim($percent), $matches)) {
            throw new DomainException('A depreciation percentage must have at most two decimal places.');
        }

        $basisPoints = ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
        if ($basisPoints > 10000 || ($positive && $basisPoints === 0)) {
            throw new DomainException('A depreciation percentage must be within its supported range.');
        }

        return $basisPoints;
    }

    private function roundedRatio(int $amount, int $numerator, int $denominator): int
    {
        if ($amount <= 0 || $numerator <= 0) {
            return 0;
        }

        return intdiv(($amount * $numerator) + intdiv($denominator, 2), $denominator);
    }
}
