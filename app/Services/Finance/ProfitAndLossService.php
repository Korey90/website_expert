<?php

namespace App\Services\Finance;

use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProfitAndLossService
{
    private const SECTION_KEYS = [
        'sales',
        'cost_of_sales',
        'operating_expenses',
        'other_income',
        'other_expenses',
        'corporation_tax',
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

        $current = $this->aggregate($businessId, $period);
        $comparison = $comparisonPeriod ? $this->aggregate($businessId, $comparisonPeriod) : collect();
        $accountIds = $current->keys()->merge($comparison->keys())->unique()->values();
        $sectionRows = collect(self::SECTION_KEYS)->mapWithKeys(fn (string $key): array => [$key => collect()]);
        $currentSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);
        $comparisonSectionPennies = array_fill_keys(self::SECTION_KEYS, 0);

        LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('type', ['income', 'expense'])
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
                    'amount_gbp' => $this->penniesToMoney($currentPennies),
                    'comparison_gbp' => $comparisonPeriod ? $this->penniesToMoney($comparisonPennies) : null,
                    'variance_gbp' => $comparisonPeriod ? $this->penniesToMoney($currentPennies - $comparisonPennies) : null,
                ]);
            });

        $currentMetrics = $this->metrics($currentSectionPennies);
        $comparisonMetrics = $this->metrics($comparisonSectionPennies);
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
                $this->journalCount($businessId, $period),
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
    private function aggregate(string $businessId, AccountingPeriod $period): Collection
    {
        return JournalLine::withoutGlobalScopes()
            ->selectRaw('journal_lines.ledger_account_id, SUM(journal_lines.debit_gbp) AS debit_total, SUM(journal_lines.credit_gbp) AS credit_total')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_lines.business_id', $businessId)
            ->where('journal_entries.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('ledger_accounts.type', ['income', 'expense'])
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '>=', $period->start_date->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $period->end_date->toDateString())
            ->groupBy('journal_lines.ledger_account_id')
            ->get()
            ->keyBy('ledger_account_id');
    }

    private function section(LedgerAccount $account): string
    {
        if ($account->type === 'income') {
            return $account->subtype === 'sales' ? 'sales' : 'other_income';
        }

        return match ($account->subtype) {
            'cost_of_sales' => 'cost_of_sales',
            'corporation_tax' => 'corporation_tax',
            'fx_loss' => 'other_expenses',
            default => 'operating_expenses',
        };
    }

    private function accountAmount(LedgerAccount $account, ?object $aggregate): int
    {
        $debit = $this->moneyToPennies($aggregate?->debit_total);
        $credit = $this->moneyToPennies($aggregate?->credit_total);

        return $account->type === 'income' ? $credit - $debit : $debit - $credit;
    }

    /**
     * @param  array<string, int>  $sections
     * @return array<string, int>
     */
    private function metrics(array $sections): array
    {
        $grossProfit = $sections['sales'] - $sections['cost_of_sales'];
        $operatingProfit = $grossProfit - $sections['operating_expenses'];
        $profitBeforeTax = $operatingProfit + $sections['other_income'] - $sections['other_expenses'];

        return [
            ...$sections,
            'gross_profit' => $grossProfit,
            'operating_profit' => $operatingProfit,
            'profit_before_tax' => $profitBeforeTax,
            'net_profit' => $profitBeforeTax - $sections['corporation_tax'],
            'total_income' => $sections['sales'] + $sections['other_income'],
            'total_expenses' => $sections['cost_of_sales'] + $sections['operating_expenses']
                + $sections['other_expenses'] + $sections['corporation_tax'],
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
        ];

        foreach ($current as $key => $pennies) {
            $totals[$key.'_gbp'] = $this->penniesToMoney($pennies);
            $totals['comparison_'.$key.'_gbp'] = $hasComparison ? $this->penniesToMoney($comparison[$key]) : null;
            $totals['variance_'.$key.'_gbp'] = $hasComparison ? $this->penniesToMoney($pennies - $comparison[$key]) : null;
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

    private function journalCount(string $businessId, AccountingPeriod $period): int
    {
        return JournalEntry::withoutGlobalScopes()
            ->join('journal_lines', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('ledger_accounts', 'ledger_accounts.id', '=', 'journal_lines.ledger_account_id')
            ->where('journal_entries.business_id', $businessId)
            ->where('journal_lines.business_id', $businessId)
            ->where('ledger_accounts.business_id', $businessId)
            ->whereIn('ledger_accounts.type', ['income', 'expense'])
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereDate('journal_entries.entry_date', '>=', $period->start_date->toDateString())
            ->whereDate('journal_entries.entry_date', '<=', $period->end_date->toDateString())
            ->distinct('journal_entries.id')
            ->count('journal_entries.id');
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
