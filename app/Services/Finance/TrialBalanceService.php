<?php

namespace App\Services\Finance;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TrialBalanceService
{
    /**
     * @return array{period: object, comparison_period: ?object, rows: Collection<int, object>, totals: object}
     */
    public function report(string $businessId, int $periodId, ?int $comparisonPeriodId = null): array
    {
        $period = $this->period($businessId, $periodId, 'selected');
        $comparisonPeriod = $comparisonPeriodId !== null
            ? $this->period($businessId, $comparisonPeriodId, 'comparison')
            : null;

        if ($comparisonPeriod?->is($period)) {
            throw new InvalidArgumentException('The comparison period must differ from the selected period.');
        }

        $opening = $this->aggregate($businessId, null, $period->start_date->copy()->subDay()->toDateString());
        $activity = $this->aggregate(
            $businessId,
            $period->start_date->toDateString(),
            $period->end_date->toDateString(),
        );
        $comparison = $comparisonPeriod
            ? $this->aggregate($businessId, null, $comparisonPeriod->end_date->toDateString())
            : collect();
        $accountIds = $opening->keys()
            ->merge($activity->keys())
            ->merge($comparison->keys())
            ->unique()
            ->values();
        $totals = $this->emptyTotals($comparisonPeriod !== null);

        $rows = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $accountIds)
            ->orderBy('code')
            ->get()
            ->map(function (LedgerAccount $account) use ($opening, $activity, $comparison, $comparisonPeriod, &$totals): object {
                $openingAmounts = $this->amounts($opening->get($account->id));
                $activityAmounts = $this->amounts($activity->get($account->id));
                $comparisonAmounts = $this->amounts($comparison->get($account->id));
                $openingBalance = $openingAmounts['debit'] - $openingAmounts['credit'];
                $closingBalance = $openingBalance + $activityAmounts['debit'] - $activityAmounts['credit'];
                $comparisonBalance = $comparisonAmounts['debit'] - $comparisonAmounts['credit'];
                $openingSplit = $this->splitBalance($openingBalance);
                $closingSplit = $this->splitBalance($closingBalance);
                $comparisonSplit = $comparisonPeriod ? $this->splitBalance($comparisonBalance) : null;
                $varianceSplit = $comparisonPeriod
                    ? $this->splitBalance($closingBalance - $comparisonBalance)
                    : null;

                $totals['opening_debit'] += $openingSplit['debit'];
                $totals['opening_credit'] += $openingSplit['credit'];
                $totals['period_debit'] += $activityAmounts['debit'];
                $totals['period_credit'] += $activityAmounts['credit'];
                $totals['closing_debit'] += $closingSplit['debit'];
                $totals['closing_credit'] += $closingSplit['credit'];

                if ($comparisonSplit && $varianceSplit) {
                    $totals['comparison_debit'] += $comparisonSplit['debit'];
                    $totals['comparison_credit'] += $comparisonSplit['credit'];
                    $totals['variance_debit'] += $varianceSplit['debit'];
                    $totals['variance_credit'] += $varianceSplit['credit'];
                }

                return (object) [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'is_active' => $account->is_active,
                    'opening_debit_gbp' => $this->penniesToMoney($openingSplit['debit']),
                    'opening_credit_gbp' => $this->penniesToMoney($openingSplit['credit']),
                    'period_debit_gbp' => $this->penniesToMoney($activityAmounts['debit']),
                    'period_credit_gbp' => $this->penniesToMoney($activityAmounts['credit']),
                    'closing_debit_gbp' => $this->penniesToMoney($closingSplit['debit']),
                    'closing_credit_gbp' => $this->penniesToMoney($closingSplit['credit']),
                    'comparison_debit_gbp' => $comparisonSplit ? $this->penniesToMoney($comparisonSplit['debit']) : null,
                    'comparison_credit_gbp' => $comparisonSplit ? $this->penniesToMoney($comparisonSplit['credit']) : null,
                    'variance_debit_gbp' => $varianceSplit ? $this->penniesToMoney($varianceSplit['debit']) : null,
                    'variance_credit_gbp' => $varianceSplit ? $this->penniesToMoney($varianceSplit['credit']) : null,
                ];
            })
            ->values();

        return [
            'period' => $this->periodData($period),
            'comparison_period' => $comparisonPeriod ? $this->periodData($comparisonPeriod) : null,
            'rows' => $rows,
            'totals' => $this->totalsData($totals, $rows->count(), $this->journalCount($businessId, $period)),
        ];
    }

    private function period(string $businessId, int $periodId, string $label): AccountingPeriod
    {
        $period = AccountingPeriod::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->find($periodId);

        if (! $period) {
            throw new InvalidArgumentException("The {$label} accounting period does not belong to this business.");
        }

        return $period;
    }

    /** @return Collection<int, object> */
    private function aggregate(string $businessId, ?string $dateFrom, string $dateTo): Collection
    {
        return JournalLine::withoutGlobalScopes()
            ->selectRaw('journal_lines.ledger_account_id, SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->when($dateFrom !== null, fn ($query) => $query->whereDate('journal_entries.entry_date', '>=', $dateFrom))
            ->whereDate('journal_entries.entry_date', '<=', $dateTo)
            ->groupBy('journal_lines.ledger_account_id')
            ->get()
            ->keyBy('ledger_account_id');
    }

    /** @return array{debit: int, credit: int} */
    private function amounts(?object $aggregate): array
    {
        return [
            'debit' => $this->moneyToPennies($aggregate?->debit_total),
            'credit' => $this->moneyToPennies($aggregate?->credit_total),
        ];
    }

    /** @return array{debit: int, credit: int} */
    private function splitBalance(int $signedPennies): array
    {
        return [
            'debit' => max($signedPennies, 0),
            'credit' => max(-$signedPennies, 0),
        ];
    }

    /** @return array<string, int|null> */
    private function emptyTotals(bool $hasComparison): array
    {
        return [
            'opening_debit' => 0,
            'opening_credit' => 0,
            'period_debit' => 0,
            'period_credit' => 0,
            'closing_debit' => 0,
            'closing_credit' => 0,
            'comparison_debit' => $hasComparison ? 0 : null,
            'comparison_credit' => $hasComparison ? 0 : null,
            'variance_debit' => $hasComparison ? 0 : null,
            'variance_credit' => $hasComparison ? 0 : null,
        ];
    }

    /** @param array<string, int|null> $totals */
    private function totalsData(array $totals, int $accountCount, int $journalCount): object
    {
        return (object) [
            'opening_debit_gbp' => $this->penniesToMoney($totals['opening_debit']),
            'opening_credit_gbp' => $this->penniesToMoney($totals['opening_credit']),
            'period_debit_gbp' => $this->penniesToMoney($totals['period_debit']),
            'period_credit_gbp' => $this->penniesToMoney($totals['period_credit']),
            'closing_debit_gbp' => $this->penniesToMoney($totals['closing_debit']),
            'closing_credit_gbp' => $this->penniesToMoney($totals['closing_credit']),
            'closing_difference_gbp' => $this->penniesToMoney($totals['closing_debit'] - $totals['closing_credit']),
            'comparison_debit_gbp' => $totals['comparison_debit'] !== null ? $this->penniesToMoney($totals['comparison_debit']) : null,
            'comparison_credit_gbp' => $totals['comparison_credit'] !== null ? $this->penniesToMoney($totals['comparison_credit']) : null,
            'variance_debit_gbp' => $totals['variance_debit'] !== null ? $this->penniesToMoney($totals['variance_debit']) : null,
            'variance_credit_gbp' => $totals['variance_credit'] !== null ? $this->penniesToMoney($totals['variance_credit']) : null,
            'account_count' => $accountCount,
            'journal_count' => $journalCount,
            'is_balanced' => $totals['closing_debit'] === $totals['closing_credit'],
        ];
    }

    private function periodData(AccountingPeriod $period): object
    {
        return (object) [
            'id' => $period->id,
            'name' => $period->name,
            'start_date' => $period->start_date->toDateString(),
            'end_date' => $period->end_date->toDateString(),
            'status' => $period->status,
        ];
    }

    private function journalCount(string $businessId, AccountingPeriod $period): int
    {
        return JournalEntry::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('entry_date', '>=', $period->start_date->toDateString())
            ->whereDate('entry_date', '<=', $period->end_date->toDateString())
            ->count();
    }

    private function moneyToPennies(mixed $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $unsigned)) {
            throw new InvalidArgumentException('A ledger aggregate must be a decimal amount with at most two fraction digits.');
        }

        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $pennies = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$pennies : $pennies;
    }

    private function penniesToMoney(?int $pennies): float
    {
        return round(($pennies ?? 0) / 100, 2);
    }
}
