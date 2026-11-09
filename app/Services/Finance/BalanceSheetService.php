<?php

namespace App\Services\Finance;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BalanceSheetService
{
    private const SECTION_KEYS = ['assets', 'liabilities', 'equity'];

    private const METRIC_KEYS = [
        'total_assets',
        'total_liabilities',
        'ledger_equity',
        'earnings_before_period',
        'period_earnings',
        'accumulated_earnings',
        'total_equity',
        'liabilities_and_equity',
        'difference',
    ];

    /**
     * @return array{period: object, comparison_period: ?object, sections: Collection<int, object>, totals: object}
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

        $current = $this->balanceAggregate($businessId, $period->end_date->toDateString());
        $comparison = $comparisonPeriod
            ? $this->balanceAggregate($businessId, $comparisonPeriod->end_date->toDateString())
            : collect();
        $accountIds = $current->keys()->merge($comparison->keys())->unique()->values();
        $sectionRows = collect(self::SECTION_KEYS)->mapWithKeys(fn (string $key): array => [$key => collect()]);
        $currentSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);
        $comparisonSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);

        LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->whereIn('id', $accountIds)
            ->orderBy('code')
            ->get()
            ->each(function (LedgerAccount $account) use (
                $current,
                $comparison,
                $comparisonPeriod,
                $sectionRows,
                &$currentSectionPennies,
                &$comparisonSectionPennies,
            ): void {
                $section = $this->section($account);
                $currentPennies = $this->accountAmount($account, $current->get($account->id));
                $comparisonPennies = $this->accountAmount($account, $comparison->get($account->id));
                $currentSectionPennies[$section] += $currentPennies;
                $comparisonSectionPennies[$section] += $comparisonPennies;

                $sectionRows[$section]->push((object) [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'is_active' => $account->is_active,
                    'is_synthetic' => false,
                    'synthetic_key' => null,
                    'amount_gbp' => $this->penniesToMoney($currentPennies),
                    'comparison_gbp' => $comparisonPeriod ? $this->penniesToMoney($comparisonPennies) : null,
                    'variance_gbp' => $comparisonPeriod ? $this->penniesToMoney($currentPennies - $comparisonPennies) : null,
                ]);
            });

        $accountCount = $sectionRows->sum(fn (Collection $rows): int => $rows->count());
        $currentEarnings = $this->earnings($businessId, $period);
        $comparisonEarnings = $comparisonPeriod
            ? $this->earnings($businessId, $comparisonPeriod)
            : ['before' => 0, 'period' => 0];

        foreach (['before' => 'earnings_before_period', 'period' => 'period_earnings'] as $source => $key) {
            $currentPennies = $currentEarnings[$source];
            $comparisonPennies = $comparisonEarnings[$source];
            $sectionRows['equity']->push((object) [
                'account_id' => null,
                'code' => null,
                'name' => null,
                'type' => 'equity',
                'subtype' => null,
                'is_active' => true,
                'is_synthetic' => true,
                'synthetic_key' => $key,
                'amount_gbp' => $this->penniesToMoney($currentPennies),
                'comparison_gbp' => $comparisonPeriod ? $this->penniesToMoney($comparisonPennies) : null,
                'variance_gbp' => $comparisonPeriod ? $this->penniesToMoney($currentPennies - $comparisonPennies) : null,
            ]);
        }

        $currentMetrics = $this->metrics($currentSectionPennies, $currentEarnings);
        $comparisonMetrics = $this->metrics($comparisonSectionPennies, $comparisonEarnings);
        $currentSectionPennies['equity'] = $currentMetrics['total_equity'];
        $comparisonSectionPennies['equity'] = $comparisonMetrics['total_equity'];

        $sections = collect(self::SECTION_KEYS)->map(function (string $key) use (
            $sectionRows,
            $currentSectionPennies,
            $comparisonSectionPennies,
            $comparisonPeriod,
        ): object {
            return (object) [
                'key' => $key,
                'rows' => $sectionRows[$key]->values(),
                'amount_gbp' => $this->penniesToMoney($currentSectionPennies[$key]),
                'comparison_gbp' => $comparisonPeriod ? $this->penniesToMoney($comparisonSectionPennies[$key]) : null,
                'variance_gbp' => $comparisonPeriod
                    ? $this->penniesToMoney($currentSectionPennies[$key] - $comparisonSectionPennies[$key])
                    : null,
            ];
        });

        return [
            'period' => $this->periodData($period),
            'comparison_period' => $comparisonPeriod ? $this->periodData($comparisonPeriod) : null,
            'sections' => $sections,
            'totals' => $this->totalsData(
                $currentMetrics,
                $comparisonMetrics,
                $comparisonPeriod !== null,
                $accountCount,
                $this->journalCount($businessId, $period->end_date->toDateString()),
            ),
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
    private function balanceAggregate(string $businessId, string $endDate): Collection
    {
        return JournalLine::withoutGlobalScopes()
            ->selectRaw('journal_lines.ledger_account_id, SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('ledger_accounts.type', ['asset', 'liability', 'equity'])
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '<=', $endDate)
            ->groupBy('journal_lines.ledger_account_id')
            ->get()
            ->keyBy('ledger_account_id');
    }

    /** @return array{before: int, period: int} */
    private function earnings(string $businessId, AccountingPeriod $period): array
    {
        return [
            'before' => $this->profitBetween(
                $businessId,
                null,
                $period->start_date->copy()->subDay()->toDateString(),
            ),
            'period' => $this->profitBetween(
                $businessId,
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ),
        ];
    }

    private function profitBetween(string $businessId, ?string $startDate, string $endDate): int
    {
        $query = JournalLine::withoutGlobalScopes()
            ->selectRaw('SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('ledger_accounts.type', ['income', 'expense'])
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '<=', $endDate);

        if ($startDate !== null) {
            $query->whereDate('journal_entries.entry_date', '>=', $startDate);
        }

        $aggregate = $query->first();

        return $this->moneyToPennies($aggregate?->credit_total) - $this->moneyToPennies($aggregate?->debit_total);
    }

    private function section(LedgerAccount $account): string
    {
        return match ($account->type) {
            'asset' => 'assets',
            'liability' => 'liabilities',
            default => 'equity',
        };
    }

    private function accountAmount(LedgerAccount $account, ?object $aggregate): int
    {
        $debit = $this->moneyToPennies($aggregate?->debit_total);
        $credit = $this->moneyToPennies($aggregate?->credit_total);

        return $account->type === 'asset' ? $debit - $credit : $credit - $debit;
    }

    /**
     * @param  array<string, int>  $sections
     * @param  array{before: int, period: int}  $earnings
     * @return array<string, int>
     */
    private function metrics(array $sections, array $earnings): array
    {
        $accumulatedEarnings = $earnings['before'] + $earnings['period'];
        $totalEquity = $sections['equity'] + $accumulatedEarnings;
        $liabilitiesAndEquity = $sections['liabilities'] + $totalEquity;

        return [
            'total_assets' => $sections['assets'],
            'total_liabilities' => $sections['liabilities'],
            'ledger_equity' => $sections['equity'],
            'earnings_before_period' => $earnings['before'],
            'period_earnings' => $earnings['period'],
            'accumulated_earnings' => $accumulatedEarnings,
            'total_equity' => $totalEquity,
            'liabilities_and_equity' => $liabilitiesAndEquity,
            'difference' => $sections['assets'] - $liabilitiesAndEquity,
        ];
    }

    /**
     * @param  array<string, int>  $current
     * @param  array<string, int>  $comparison
     */
    private function totalsData(array $current, array $comparison, bool $hasComparison, int $accountCount, int $journalCount): object
    {
        $totals = [
            'account_count' => $accountCount,
            'journal_count' => $journalCount,
            'is_balanced' => $current['difference'] === 0,
            'comparison_is_balanced' => $hasComparison ? $comparison['difference'] === 0 : null,
        ];

        foreach (self::METRIC_KEYS as $key) {
            $totals[$key.'_gbp'] = $this->penniesToMoney($current[$key]);
            $totals['comparison_'.$key.'_gbp'] = $hasComparison ? $this->penniesToMoney($comparison[$key]) : null;
            $totals['variance_'.$key.'_gbp'] = $hasComparison
                ? $this->penniesToMoney($current[$key] - $comparison[$key])
                : null;
        }

        return (object) $totals;
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

    private function journalCount(string $businessId, string $endDate): int
    {
        return JournalEntry::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('entry_date', '<=', $endDate)
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

    private function penniesToMoney(int $pennies): float
    {
        return round($pennies / 100, 2);
    }
}
