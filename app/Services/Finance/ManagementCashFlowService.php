<?php

namespace App\Services\Finance;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ManagementCashFlowService
{
    private const SECTION_KEYS = ['operating', 'investing', 'financing'];

    private const INVESTING_ASSET_SUBTYPES = [
        'fixed_asset',
        'contra_asset',
        'investment',
        'intangible_asset',
    ];

    private const FINANCING_LIABILITY_SUBTYPES = [
        'loan',
        'borrowings',
        'finance_lease',
        'director_loan',
        'overdraft',
    ];

    private const METRIC_KEYS = [
        'opening_cash',
        'cash_inflows',
        'cash_outflows',
        'operating_cash_flow',
        'investing_cash_flow',
        'financing_cash_flow',
        'net_cash_flow',
        'closing_cash',
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

        $cashAccountIds = $this->cashAccountIds($businessId);
        $current = $this->counterpartAggregate($businessId, $period, $cashAccountIds);
        $comparison = $comparisonPeriod
            ? $this->counterpartAggregate($businessId, $comparisonPeriod, $cashAccountIds)
            : collect();
        $accountIds = $current->keys()->merge($comparison->keys())->unique()->values();
        $sectionRows = collect(self::SECTION_KEYS)->mapWithKeys(fn (string $key): array => [$key => collect()]);
        $currentSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);
        $comparisonSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);

        LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
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
                $currentPennies = $this->counterpartAmount($current->get($account->id));
                $comparisonPennies = $this->counterpartAmount($comparison->get($account->id));
                $currentSectionPennies[$section] += $currentPennies;
                $comparisonSectionPennies[$section] += $comparisonPennies;

                $sectionRows[$section]->push((object) [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'is_active' => $account->is_active,
                    'amount_gbp' => $this->penniesToMoney($currentPennies),
                    'comparison_gbp' => $comparisonPeriod ? $this->penniesToMoney($comparisonPennies) : null,
                    'variance_gbp' => $comparisonPeriod ? $this->penniesToMoney($currentPennies - $comparisonPennies) : null,
                ]);
            });

        $currentActivity = $this->cashActivity($businessId, $period, $cashAccountIds);
        $comparisonActivity = $comparisonPeriod
            ? $this->cashActivity($businessId, $comparisonPeriod, $cashAccountIds)
            : ['inflows' => 0, 'outflows' => 0, 'net' => 0, 'journals' => 0];
        $currentMetrics = $this->metrics($businessId, $period, $cashAccountIds, $currentSectionPennies, $currentActivity);
        $comparisonMetrics = $comparisonPeriod
            ? $this->metrics($businessId, $comparisonPeriod, $cashAccountIds, $comparisonSectionPennies, $comparisonActivity)
            : array_fill_keys(self::METRIC_KEYS, 0);

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
                $sectionRows->sum(fn (Collection $rows): int => $rows->count()),
                $cashAccountIds->count(),
                $currentActivity['journals'],
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

    /** @return Collection<int, int> */
    private function cashAccountIds(string $businessId): Collection
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where('type', 'asset')
            ->where('subtype', 'cash')
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $cashAccountIds
     * @return Collection<int, object>
     */
    private function counterpartAggregate(string $businessId, AccountingPeriod $period, Collection $cashAccountIds): Collection
    {
        if ($cashAccountIds->isEmpty()) {
            return collect();
        }

        return JournalLine::withoutGlobalScopes()
            ->selectRaw('journal_lines.ledger_account_id, SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereNotIn('journal_lines.ledger_account_id', $cashAccountIds)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '>=', $period->start_date->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $period->end_date->toDateString())
            ->whereIn('journal_lines.journal_entry_id', function ($query) use ($businessId, $cashAccountIds): void {
                $query->select('cash_lines.journal_entry_id')
                    ->from('journal_lines as cash_lines')
                    ->where('cash_lines.business_id', $businessId)
                    ->whereIn('cash_lines.ledger_account_id', $cashAccountIds);
            })
            ->groupBy('journal_lines.ledger_account_id')
            ->get()
            ->keyBy('ledger_account_id');
    }

    /**
     * @param  Collection<int, int>  $cashAccountIds
     * @return array{inflows: int, outflows: int, net: int, journals: int}
     */
    private function cashActivity(string $businessId, AccountingPeriod $period, Collection $cashAccountIds): array
    {
        if ($cashAccountIds->isEmpty()) {
            return ['inflows' => 0, 'outflows' => 0, 'net' => 0, 'journals' => 0];
        }

        $journals = JournalLine::withoutGlobalScopes()
            ->selectRaw('journal_lines.journal_entry_id, SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->whereIn('journal_lines.ledger_account_id', $cashAccountIds)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '>=', $period->start_date->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $period->end_date->toDateString())
            ->groupBy('journal_lines.journal_entry_id')
            ->get();
        $inflows = 0;
        $outflows = 0;

        foreach ($journals as $journal) {
            $movement = $this->moneyToPennies($journal->debit_total) - $this->moneyToPennies($journal->credit_total);

            if ($movement >= 0) {
                $inflows += $movement;
            } else {
                $outflows += abs($movement);
            }
        }

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'net' => $inflows - $outflows,
            'journals' => $journals->count(),
        ];
    }

    private function section(LedgerAccount $account): string
    {
        if ($account->type === 'equity') {
            return 'financing';
        }

        if ($account->type === 'liability' && in_array($account->subtype, self::FINANCING_LIABILITY_SUBTYPES, true)) {
            return 'financing';
        }

        if ($account->type === 'asset' && in_array($account->subtype, self::INVESTING_ASSET_SUBTYPES, true)) {
            return 'investing';
        }

        return 'operating';
    }

    private function counterpartAmount(?object $aggregate): int
    {
        return $this->moneyToPennies($aggregate?->credit_total) - $this->moneyToPennies($aggregate?->debit_total);
    }

    /**
     * @param  Collection<int, int>  $cashAccountIds
     * @param  array<string, int>  $sections
     * @param  array{inflows: int, outflows: int, net: int, journals: int}  $activity
     * @return array<string, int>
     */
    private function metrics(
        string $businessId,
        AccountingPeriod $period,
        Collection $cashAccountIds,
        array $sections,
        array $activity,
    ): array {
        $openingCash = $this->cashBalance(
            $businessId,
            $cashAccountIds,
            $period->start_date->copy()->subDay()->toDateString(),
        );
        $closingCash = $this->cashBalance($businessId, $cashAccountIds, $period->end_date->toDateString());
        $netCashFlow = array_sum($sections);

        return [
            'opening_cash' => $openingCash,
            'cash_inflows' => $activity['inflows'],
            'cash_outflows' => $activity['outflows'],
            'operating_cash_flow' => $sections['operating'],
            'investing_cash_flow' => $sections['investing'],
            'financing_cash_flow' => $sections['financing'],
            'net_cash_flow' => $netCashFlow,
            'closing_cash' => $closingCash,
            'difference' => $openingCash + $netCashFlow - $closingCash,
        ];
    }

    /** @param Collection<int, int> $cashAccountIds */
    private function cashBalance(string $businessId, Collection $cashAccountIds, string $endDate): int
    {
        if ($cashAccountIds->isEmpty()) {
            return 0;
        }

        $aggregate = JournalLine::withoutGlobalScopes()
            ->selectRaw('SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->whereIn('journal_lines.ledger_account_id', $cashAccountIds)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '<=', $endDate)
            ->first();

        return $this->moneyToPennies($aggregate?->debit_total) - $this->moneyToPennies($aggregate?->credit_total);
    }

    /**
     * @param  array<string, int>  $current
     * @param  array<string, int>  $comparison
     */
    private function totalsData(
        array $current,
        array $comparison,
        bool $hasComparison,
        int $accountCount,
        int $cashAccountCount,
        int $journalCount,
    ): object {
        $totals = [
            'account_count' => $accountCount,
            'cash_account_count' => $cashAccountCount,
            'journal_count' => $journalCount,
            'is_reconciled' => $current['difference'] === 0,
            'comparison_is_reconciled' => $hasComparison ? $comparison['difference'] === 0 : null,
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
