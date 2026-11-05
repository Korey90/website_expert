<?php

namespace App\Services\Finance;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class GeneralLedgerService
{
    /**
     * @return array{accounts: Collection<int, object>, totals: object}
     */
    public function report(string $businessId, string $dateFrom, string $dateTo, ?int $accountId = null): array
    {
        $from = $this->date($dateFrom, 'start');
        $to = $this->date($dateTo, 'end');

        if ($to->lt($from)) {
            throw new InvalidArgumentException('The general ledger end date cannot precede its start date.');
        }

        if ($accountId !== null && ! LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereKey($accountId)
            ->exists()) {
            throw new InvalidArgumentException('The selected ledger account does not belong to this business.');
        }

        $statuses = [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED];
        $lineQuery = JournalLine::withoutGlobalScopes()
            ->where('journal_lines.business_id', $businessId)
            ->when($accountId, fn ($query) => $query->where('ledger_account_id', $accountId))
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', $statuses)
                ->whereDate('entry_date', '>=', $from->toDateString())
                ->whereDate('entry_date', '<=', $to->toDateString()))
            ->with([
                'journalEntry.accountingPeriod',
                'ledgerAccount',
                'taxCode',
                'client',
                'supplier',
                'project',
            ])
            ->get()
            ->sortBy(fn (JournalLine $line): string => implode('|', [
                $line->ledgerAccount->code,
                $line->journalEntry->entry_date->format('Y-m-d'),
                $line->journalEntry->entry_number,
                str_pad((string) $line->line_number, 5, '0', STR_PAD_LEFT),
            ]));

        $openingBalances = JournalLine::withoutGlobalScopes()
            ->selectRaw('ledger_account_id, SUM(debit_gbp) AS debit_total, SUM(credit_gbp) AS credit_total')
            ->where('journal_lines.business_id', $businessId)
            ->when($accountId, fn ($query) => $query->where('ledger_account_id', $accountId))
            ->whereHas('journalEntry', fn ($query) => $query
                ->whereIn('status', $statuses)
                ->whereDate('entry_date', '<', $from->toDateString()))
            ->groupBy('ledger_account_id')
            ->get()
            ->keyBy('ledger_account_id');

        $accountIds = $lineQuery->pluck('ledger_account_id')
            ->merge($openingBalances->keys())
            ->when($accountId, fn (Collection $ids) => $ids->push($accountId))
            ->unique()
            ->values();

        $accounts = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $accountIds)
            ->orderBy('code')
            ->get()
            ->map(function (LedgerAccount $account) use ($lineQuery, $openingBalances): object {
                $opening = $openingBalances->get($account->id);
                $runningPennies = $this->moneyToPennies($opening?->debit_total)
                    - $this->moneyToPennies($opening?->credit_total);

                $rows = $lineQuery
                    ->where('ledger_account_id', $account->id)
                    ->values()
                    ->map(function (JournalLine $line) use (&$runningPennies): object {
                        $runningPennies += $this->moneyToPennies($line->debit_gbp)
                            - $this->moneyToPennies($line->credit_gbp);

                        return (object) [
                            'id' => $line->id,
                            'entry_id' => $line->journal_entry_id,
                            'entry_number' => $line->journalEntry->entry_number,
                            'entry_date' => $line->journalEntry->entry_date->toDateString(),
                            'period' => $line->journalEntry->accountingPeriod->name,
                            'status' => $line->journalEntry->status,
                            'description' => $line->journalEntry->description,
                            'memo' => $line->memo,
                            'debit_gbp' => $this->penniesToMoney($this->moneyToPennies($line->debit_gbp)),
                            'credit_gbp' => $this->penniesToMoney($this->moneyToPennies($line->credit_gbp)),
                            'balance' => $this->balance($runningPennies),
                            'tax_code' => $line->taxCode?->code,
                            'client' => $line->client?->company_name,
                            'supplier' => $line->supplier?->name,
                            'project' => $line->project?->title,
                        ];
                    });

                $openingPennies = $this->moneyToPennies($opening?->debit_total)
                    - $this->moneyToPennies($opening?->credit_total);

                return (object) [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'normal_balance' => $account->normal_balance,
                    'opening_balance' => $this->balance($openingPennies),
                    'closing_balance' => $this->balance($runningPennies),
                    'debit_gbp' => round((float) $rows->sum('debit_gbp'), 2),
                    'credit_gbp' => round((float) $rows->sum('credit_gbp'), 2),
                    'rows' => $rows,
                ];
            });

        $debitPennies = $lineQuery->sum(fn (JournalLine $line): int => $this->moneyToPennies($line->debit_gbp));
        $creditPennies = $lineQuery->sum(fn (JournalLine $line): int => $this->moneyToPennies($line->credit_gbp));

        return [
            'accounts' => $accounts,
            'totals' => (object) [
                'debit_gbp' => $this->penniesToMoney($debitPennies),
                'credit_gbp' => $this->penniesToMoney($creditPennies),
                'difference_gbp' => $this->penniesToMoney($debitPennies - $creditPennies),
                'line_count' => $lineQuery->count(),
            ],
        ];
    }

    private function date(string $value, string $boundary): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', trim($value));
        } catch (\Throwable) {
            $date = null;
        }

        if (! $date || $date->format('Y-m-d') !== trim($value)) {
            throw new InvalidArgumentException("The general ledger {$boundary} date must use a valid YYYY-MM-DD value.");
        }

        return $date;
    }

    private function moneyToPennies(mixed $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }

    private function penniesToMoney(int $pennies): float
    {
        return round($pennies / 100, 2);
    }

    /** @return object{amount: float, side: string} */
    private function balance(int $pennies): object
    {
        return (object) [
            'amount' => $this->penniesToMoney(abs($pennies)),
            'side' => $pennies >= 0 ? 'debit' : 'credit',
        ];
    }
}
