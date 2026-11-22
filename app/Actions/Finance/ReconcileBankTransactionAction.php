<?php

namespace App\Actions\Finance;

use App\Data\Finance\BankTransactionMatchCandidate;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use App\Services\Finance\BankTransactionMatchingService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReconcileBankTransactionAction
{
    public function __construct(private readonly BankTransactionMatchingService $matchingService) {}

    /**
     * @param  list<array{candidate_key: string, amount: int|float|string}>  $allocations
     */
    public function execute(
        BankTransaction $transaction,
        User $actor,
        array $allocations,
        ?string $notes = null,
    ): BankReconciliation {
        $this->authorize($transaction, $actor);

        if ($allocations === [] || count($allocations) > 50) {
            throw new DomainException('At least one and no more than 50 reconciliation allocations are required.');
        }

        $notes = trim((string) $notes);

        if (mb_strlen($notes) > 2000) {
            throw new DomainException('Reconciliation notes may not exceed 2000 characters.');
        }

        return DB::transaction(function () use ($transaction, $actor, $allocations, $notes): BankReconciliation {
            $lockedTransaction = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $transaction->business_id)
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedTransaction->reconciliation_status, [
                BankTransaction::STATUS_UNMATCHED,
                BankTransaction::STATUS_PARTIALLY_RECONCILED,
            ], true)) {
                throw new DomainException('The bank transaction is already fully reconciled.');
            }

            $keys = array_map(
                fn (array $allocation): string => trim((string) ($allocation['candidate_key'] ?? '')),
                $allocations,
            );

            if (in_array('', $keys, true) || count(array_unique($keys)) !== count($keys)) {
                throw new DomainException('Each reconciliation candidate must be selected once.');
            }

            sort($keys);

            foreach ($keys as $key) {
                $this->lockCandidateSource($key, $lockedTransaction);
            }

            $candidates = collect($this->matchingService->findCandidates($lockedTransaction))
                ->keyBy(fn (BankTransactionMatchCandidate $candidate): string => $candidate->candidateKey);
            $allocatedBefore = $this->allocatedTransactionAmount($lockedTransaction);
            $transactionTotal = abs($this->toPennies((string) $lockedTransaction->amount));
            $transactionRemaining = $transactionTotal - $allocatedBefore;
            $prepared = [];
            $batchTotal = 0;

            foreach ($allocations as $allocation) {
                $candidateKey = trim((string) ($allocation['candidate_key'] ?? ''));
                /** @var BankTransactionMatchCandidate|null $candidate */
                $candidate = $candidates->get($candidateKey);

                if (! $candidate) {
                    throw new DomainException('The selected reconciliation candidate is no longer available.');
                }

                $amount = $this->positivePennies($allocation['amount'] ?? null);
                $candidateAvailable = abs($this->toPennies($candidate->comparableAmount));

                if ($amount > $candidateAvailable) {
                    throw new DomainException('An allocation exceeds the remaining source amount.');
                }

                $batchTotal += $amount;

                if ($batchTotal > $transactionRemaining) {
                    throw new DomainException('Reconciliation allocations exceed the remaining bank transaction amount.');
                }

                $sourceAvailable = abs($this->toPennies($candidate->originalAmount));
                $sourceAmount = (int) round($amount * ($sourceAvailable / max($candidateAvailable, 1)));

                if ($sourceAmount > $sourceAvailable) {
                    throw new DomainException('An allocation exceeds the remaining source amount.');
                }

                $prepared[] = [
                    'candidate' => $candidate,
                    'amount' => $amount,
                    'source_amount' => $sourceAmount,
                ];
            }

            $reconciliation = BankReconciliation::withoutGlobalScopes()->create([
                'business_id' => $lockedTransaction->business_id,
                'bank_transaction_id' => $lockedTransaction->id,
                'created_by' => $actor->id,
                'status' => BankReconciliation::STATUS_ACTIVE,
                'total_amount' => $this->fromPennies($batchTotal),
                'currency' => strtoupper($lockedTransaction->currency),
                'notes' => $notes !== '' ? $notes : null,
                'reconciled_at' => now(),
            ]);

            foreach ($prepared as $item) {
                /** @var BankTransactionMatchCandidate $candidate */
                $candidate = $item['candidate'];

                BankReconciliationAllocation::withoutGlobalScopes()->create([
                    'business_id' => $lockedTransaction->business_id,
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_transaction_id' => $lockedTransaction->id,
                    'reconcilable_type' => $candidate->sourceType,
                    'reconcilable_id' => $candidate->sourceId,
                    'event_type' => $candidate->eventType,
                    'candidate_key' => $candidate->candidateKey,
                    'amount' => $this->fromPennies($item['amount']),
                    'currency' => $candidate->comparableCurrency,
                    'source_amount' => $this->fromPennies($item['source_amount']),
                    'source_currency' => $candidate->originalCurrency,
                    'match_score' => $candidate->score,
                ]);
            }

            $newAllocatedTotal = $allocatedBefore + $batchTotal;
            $lockedTransaction->update([
                'reconciliation_status' => $newAllocatedTotal === $transactionTotal
                    ? BankTransaction::STATUS_RECONCILED
                    : BankTransaction::STATUS_PARTIALLY_RECONCILED,
            ]);

            return $reconciliation->load('allocations');
        }, 3);
    }

    private function authorize(BankTransaction $transaction, User $actor): void
    {
        if (! $actor->businessMemberships()
            ->where('business_id', $transaction->business_id)
            ->where('is_active', true)
            ->exists()) {
            throw new DomainException('The actor does not belong to the bank transaction business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot reconcile bank transactions.');
        }
    }

    private function lockCandidateSource(string $candidateKey, BankTransaction $transaction): Model
    {
        [$model, $id] = match (true) {
            preg_match('/^payment:(\d+):(receipt|refund)$/', $candidateKey, $matches) === 1 => [Payment::class, (int) $matches[1]],
            preg_match('/^expense_payment:(\d+)$/', $candidateKey, $matches) === 1 => [ExpensePayment::class, (int) $matches[1]],
            preg_match('/^purchase_credit_refund:(\d+)$/', $candidateKey, $matches) === 1 => [PurchaseCreditRefund::class, (int) $matches[1]],
            default => throw new DomainException('The reconciliation candidate key is invalid.'),
        };

        $source = $model::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        if (! $source) {
            throw new DomainException('The selected reconciliation source does not belong to this business.');
        }

        if ($source instanceof ExpensePayment
            && ($source->settlement_source !== ExpensePayment::SOURCE_BANK_ACCOUNT
                || $source->bank_account_id !== $transaction->bank_account_id)) {
            throw new DomainException('Only a supplier payment from this business bank account can be reconciled.');
        }

        return $source;
    }

    private function allocatedTransactionAmount(BankTransaction $transaction): int
    {
        $amount = BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount');

        return abs($this->toPennies((string) $amount));
    }

    private function positivePennies(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new DomainException('Allocation amounts must be positive values with at most two decimal places.');
        }

        $pennies = $this->toPennies($value);

        if ($pennies <= 0) {
            throw new DomainException('Allocation amounts must be greater than zero.');
        }

        return $pennies;
    }

    private function toPennies(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function fromPennies(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
