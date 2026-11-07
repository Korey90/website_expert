<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BankReconciliationReportService
{
    /**
     * @return array{accounts: Collection<int, object>, transactions: Collection<int, object>, totals: object}
     */
    public function report(string $businessId, string $dateFrom, string $dateTo, ?int $bankAccountId = null): array
    {
        $from = $this->date($dateFrom);
        $to = $this->date($dateTo);

        if ($to->lt($from)) {
            throw new InvalidArgumentException('The report end date cannot precede its start date.');
        }

        $bankAccounts = BankAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->when($bankAccountId !== null, fn ($query) => $query->whereKey($bankAccountId))
            ->orderBy('name')
            ->get();

        if ($bankAccountId !== null && $bankAccounts->isEmpty()) {
            throw new InvalidArgumentException('The selected bank account does not belong to this business.');
        }

        $accountIds = $bankAccounts->pluck('id');
        $periodTransactions = BankTransaction::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('bank_account_id', $accountIds)
            ->whereDate('booking_date', '>=', $from->toDateString())
            ->whereDate('booking_date', '<=', $to->toDateString())
            ->orderBy('booking_date')
            ->orderBy('id')
            ->get();
        $activeAllocations = $this->activeAllocationTotals(
            $businessId,
            $periodTransactions->pluck('id')->all(),
        );
        $rows = collect();
        $accounts = collect();

        foreach ($bankAccounts as $account) {
            $coverageStart = $account->opening_balance_date?->toImmutable();
            $openingMovement = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('bank_account_id', $account->id)
                ->whereDate('booking_date', '<', $from->toDateString())
                ->when($coverageStart, fn ($query) => $query->whereDate('booking_date', '>=', $coverageStart->toDateString()))
                ->sum('amount');
            $openingPennies = $this->toPennies($account->opening_balance)
                + $this->toPennies($openingMovement);
            $accountRows = $periodTransactions
                ->where('bank_account_id', $account->id)
                ->filter(fn (BankTransaction $transaction): bool => ! $coverageStart
                    || $transaction->booking_date->gte($coverageStart))
                ->map(function (BankTransaction $transaction) use ($account, $activeAllocations): object {
                    $amountPennies = $this->toPennies($transaction->amount);
                    $allocatedPennies = $this->toPennies($activeAllocations[$transaction->id] ?? 0);
                    $remainingPennies = max(0, abs($amountPennies) - $allocatedPennies);
                    $status = match (true) {
                        $remainingPennies === 0 => BankTransaction::STATUS_RECONCILED,
                        $allocatedPennies > 0 => BankTransaction::STATUS_PARTIALLY_RECONCILED,
                        default => BankTransaction::STATUS_UNMATCHED,
                    };

                    return (object) [
                        'id' => $transaction->id,
                        'bank_account_id' => $account->id,
                        'account_name' => $account->name,
                        'booking_date' => $transaction->booking_date->toDateString(),
                        'value_date' => $transaction->value_date?->toDateString(),
                        'reference' => $transaction->reference,
                        'counterparty' => $transaction->counterparty,
                        'amount' => $this->fromPennies($amountPennies),
                        'allocated_amount' => $this->fromPennies($allocatedPennies),
                        'remaining_amount' => $this->fromPennies($remainingPennies),
                        'currency' => strtoupper($account->currency),
                        'status' => $status,
                    ];
                })
                ->values();
            $inflowPennies = $accountRows
                ->filter(fn (object $row): bool => $row->amount > 0)
                ->sum(fn (object $row): int => $this->toPennies($row->amount));
            $outflowPennies = abs($accountRows
                ->filter(fn (object $row): bool => $row->amount < 0)
                ->sum(fn (object $row): int => $this->toPennies($row->amount)));
            $allocatedPennies = $accountRows->sum(
                fn (object $row): int => $this->toPennies($row->allocated_amount),
            );
            $remainingPennies = $accountRows->sum(
                fn (object $row): int => $this->toPennies($row->remaining_amount),
            );
            $absolutePennies = $allocatedPennies + $remainingPennies;

            $accounts->push((object) [
                'id' => $account->id,
                'name' => $account->name,
                'bank_name' => $account->bank_name,
                'masked_account_number' => $account->masked_account_number,
                'currency' => strtoupper($account->currency),
                'status' => $account->status,
                'coverage_start' => $coverageStart?->toDateString(),
                'opening_balance' => $this->fromPennies($openingPennies),
                'inflows' => $this->fromPennies($inflowPennies),
                'outflows' => $this->fromPennies($outflowPennies),
                'closing_balance' => $this->fromPennies($openingPennies + $inflowPennies - $outflowPennies),
                'allocated_amount' => $this->fromPennies($allocatedPennies),
                'remaining_amount' => $this->fromPennies($remainingPennies),
                'reconciliation_rate' => $absolutePennies > 0
                    ? round(($allocatedPennies / $absolutePennies) * 100, 1)
                    : 100.0,
                'transaction_count' => $accountRows->count(),
                'reconciled_count' => $accountRows->where('status', BankTransaction::STATUS_RECONCILED)->count(),
                'partial_count' => $accountRows->where('status', BankTransaction::STATUS_PARTIALLY_RECONCILED)->count(),
                'unmatched_count' => $accountRows->where('status', BankTransaction::STATUS_UNMATCHED)->count(),
            ]);
            $rows->push(...$accountRows);
        }

        return [
            'accounts' => $accounts,
            'transactions' => $rows,
            'totals' => (object) [
                'account_count' => $accounts->count(),
                'transaction_count' => $rows->count(),
                'reconciled_count' => $rows->where('status', BankTransaction::STATUS_RECONCILED)->count(),
                'partial_count' => $rows->where('status', BankTransaction::STATUS_PARTIALLY_RECONCILED)->count(),
                'unmatched_count' => $rows->where('status', BankTransaction::STATUS_UNMATCHED)->count(),
                'open_count' => $rows->whereIn('status', [
                    BankTransaction::STATUS_UNMATCHED,
                    BankTransaction::STATUS_PARTIALLY_RECONCILED,
                ])->count(),
            ],
        ];
    }

    /**
     * @param  list<int>  $transactionIds
     * @return array<int, string>
     */
    private function activeAllocationTotals(string $businessId, array $transactionIds): array
    {
        if ($transactionIds === []) {
            return [];
        }

        return BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('bank_transaction_id', $transactionIds)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->selectRaw('bank_transaction_id, SUM(amount) as allocated_amount')
            ->groupBy('bank_transaction_id')
            ->pluck('allocated_amount', 'bank_transaction_id')
            ->all();
    }

    private function date(string $value): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            $date = null;
        }

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Report dates must use a valid YYYY-MM-DD value.');
        }

        return $date;
    }

    private function toPennies(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function fromPennies(int $amount): float
    {
        return round($amount / 100, 2);
    }
}
