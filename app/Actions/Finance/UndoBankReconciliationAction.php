<?php

namespace App\Actions\Finance;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UndoBankReconciliationAction
{
    /** @var list<class-string<Model>> */
    private const SOURCE_MODELS = [
        Payment::class,
        ExpensePayment::class,
        PurchaseCreditRefund::class,
    ];

    public function execute(
        BankReconciliation $reconciliation,
        User $actor,
        string $reason,
    ): BankReconciliation {
        $this->authorize($reconciliation, $actor);
        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw new DomainException('A reconciliation reversal reason between 1 and 2000 characters is required.');
        }

        return DB::transaction(function () use ($reconciliation, $actor, $reason): BankReconciliation {
            $transaction = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $reconciliation->business_id)
                ->whereKey($reconciliation->bank_transaction_id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                throw new DomainException('The reconciliation bank transaction does not belong to this business.');
            }

            $locked = BankReconciliation::withoutGlobalScopes()
                ->where('business_id', $reconciliation->business_id)
                ->where('bank_transaction_id', $transaction->id)
                ->whereKey($reconciliation->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new DomainException('The reconciliation does not belong to this bank transaction and business.');
            }

            if ($locked->status === BankReconciliation::STATUS_REVERSED) {
                if ($locked->reversal_reason !== $reason) {
                    throw new DomainException('The reconciliation has already been reversed for a different reason.');
                }

                return $locked->load(['allocations', 'reversedBy']);
            }

            if ($locked->status !== BankReconciliation::STATUS_ACTIVE) {
                throw new DomainException('Only an active bank reconciliation can be reversed.');
            }

            $allocations = BankReconciliationAllocation::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('bank_reconciliation_id', $locked->id)
                ->orderBy('reconcilable_type')
                ->orderBy('reconcilable_id')
                ->orderBy('event_type')
                ->lockForUpdate()
                ->get();

            $this->assertBatchIntegrity($locked, $transaction, $allocations);
            $this->lockSources($locked->business_id, $allocations);
            $reversedAt = now();
            $updated = DB::table('bank_reconciliations')
                ->where('id', $locked->id)
                ->where('business_id', $locked->business_id)
                ->where('status', BankReconciliation::STATUS_ACTIVE)
                ->update([
                    'status' => BankReconciliation::STATUS_REVERSED,
                    'reversed_by' => $actor->id,
                    'reversed_at' => $reversedAt,
                    'reversal_reason' => $reason,
                    'updated_at' => $reversedAt,
                ]);

            if ($updated !== 1) {
                throw new DomainException('The reconciliation changed while it was being reversed.');
            }

            $this->refreshTransactionStatus($transaction);

            return BankReconciliation::withoutGlobalScopes()
                ->with(['allocations', 'reversedBy'])
                ->findOrFail($locked->id);
        }, 3);
    }

    private function authorize(BankReconciliation $reconciliation, User $actor): void
    {
        if (! $actor->businessMemberships()
            ->where('business_id', $reconciliation->business_id)
            ->where('is_active', true)
            ->exists()) {
            throw new DomainException('The actor does not belong to the reconciliation business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot reverse bank reconciliations.');
        }
    }

    /** @param Collection<int, BankReconciliationAllocation> $allocations */
    private function assertBatchIntegrity(
        BankReconciliation $reconciliation,
        BankTransaction $transaction,
        Collection $allocations,
    ): void {
        if ($allocations->isEmpty()) {
            throw new DomainException('An active reconciliation must contain at least one allocation.');
        }

        $allocationTotal = 0;

        foreach ($allocations as $allocation) {
            if ($allocation->bank_transaction_id !== $transaction->id
                || $allocation->currency !== $transaction->currency) {
                throw new DomainException('The reconciliation allocation audit data is inconsistent.');
            }

            $allocationTotal += $this->toPennies($allocation->amount);
        }

        if ($allocationTotal <= 0 || $allocationTotal !== $this->toPennies($reconciliation->total_amount)
            || $reconciliation->currency !== $transaction->currency) {
            throw new DomainException('The reconciliation batch total does not match its allocation audit data.');
        }
    }

    /** @param Collection<int, BankReconciliationAllocation> $allocations */
    private function lockSources(string $businessId, Collection $allocations): void
    {
        foreach ($allocations as $allocation) {
            $model = $allocation->reconcilable_type;

            if (! in_array($model, self::SOURCE_MODELS, true)) {
                throw new DomainException('The reconciliation source type is invalid.');
            }

            $source = $model::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->whereKey($allocation->reconcilable_id)
                ->lockForUpdate()
                ->first();

            if (! $source) {
                throw new DomainException('The reconciliation source no longer belongs to this business.');
            }
        }
    }

    private function refreshTransactionStatus(BankTransaction $transaction): void
    {
        $allocated = BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount');
        $allocatedPennies = $this->toPennies($allocated);
        $transactionPennies = abs($this->toPennies($transaction->amount));

        if ($allocatedPennies > $transactionPennies) {
            throw new DomainException('Active reconciliation allocations exceed the bank transaction amount.');
        }

        $status = match (true) {
            $allocatedPennies === 0 => BankTransaction::STATUS_UNMATCHED,
            $allocatedPennies === $transactionPennies => BankTransaction::STATUS_RECONCILED,
            default => BankTransaction::STATUS_PARTIALLY_RECONCILED,
        };

        $transaction->update(['reconciliation_status' => $status]);
    }

    private function toPennies(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
