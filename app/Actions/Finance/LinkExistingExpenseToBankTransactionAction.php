<?php

namespace App\Actions\Finance;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class LinkExistingExpenseToBankTransactionAction
{
    public function __construct(
        private readonly ApproveExpenseAction $approveExpense,
        private readonly PostExpenseAction $postExpense,
        private readonly RecordExpensePaymentAction $recordExpensePayment,
        private readonly ReconcileBankTransactionAction $reconcileTransaction,
    ) {}

    public function execute(
        BankTransaction $transaction,
        Expense $expense,
        User $actor,
        ?string $notes = null,
    ): BankReconciliation {
        $this->authorize($transaction, $actor);
        $notes = trim((string) $notes);

        if (mb_strlen($notes) > 2000) {
            throw new DomainException('Link notes may not exceed 2000 characters.');
        }

        return DB::transaction(function () use ($transaction, $expense, $actor, $notes): BankReconciliation {
            Business::query()->whereKey($transaction->business_id)->lockForUpdate()->firstOrFail();

            $lockedTransaction = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $transaction->business_id)
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $lockedTransaction->amount >= 0) {
                throw new DomainException('Only an outgoing bank transaction can be linked to an expense.');
            }

            if (! in_array($lockedTransaction->reconciliation_status, [
                BankTransaction::STATUS_UNMATCHED,
                BankTransaction::STATUS_PARTIALLY_RECONCILED,
            ], true)) {
                throw new DomainException('The bank transaction is already fully reconciled.');
            }

            $remainingPennies = $this->remainingTransactionPennies($lockedTransaction);

            if ($remainingPennies <= 0) {
                throw new DomainException('The bank transaction has no remaining amount to link.');
            }

            $lockedExpense = Expense::withoutGlobalScopes()
                ->where('business_id', $lockedTransaction->business_id)
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedExpense) {
                throw new DomainException('The selected expense does not belong to the bank transaction business.');
            }

            if (! in_array($lockedExpense->status, [
                Expense::STATUS_DRAFT,
                Expense::STATUS_APPROVED,
                Expense::STATUS_POSTED,
                Expense::STATUS_PARTIALLY_PAID,
            ], true)) {
                throw new DomainException('The selected expense cannot receive this bank payment.');
            }

            $bankCurrency = strtoupper((string) $lockedTransaction->currency);
            $expenseCurrency = strtoupper((string) $lockedExpense->currency);

            if ($expenseCurrency !== $bankCurrency) {
                throw new DomainException('The expense and bank transaction must use the same currency.');
            }

            if ($this->moneyToPennies($lockedExpense->amount_due) < $remainingPennies) {
                throw new DomainException('The expense outstanding amount is lower than the remaining bank transaction amount.');
            }

            $bankAccount = BankAccount::withoutGlobalScopes()
                ->where('business_id', $lockedTransaction->business_id)
                ->whereKey($lockedTransaction->bank_account_id)
                ->lockForUpdate()
                ->first();

            if (! $bankAccount || strtoupper((string) $bankAccount->currency) !== $bankCurrency) {
                throw new DomainException('The bank transaction requires a matching bank account from the same business and currency.');
            }

            if ($lockedExpense->status === Expense::STATUS_DRAFT) {
                if (! PermissionHelper::allows($actor, 'approve_finance')) {
                    throw new DomainException('The actor cannot approve the selected draft expense.');
                }

                $lockedExpense = $this->approveExpense->execute($lockedExpense, $actor);
            }

            if ($lockedExpense->status === Expense::STATUS_APPROVED) {
                $lockedExpense = $this->postExpense->execute($lockedExpense, $actor);
            }

            $bookingDate = $lockedTransaction->booking_date->toDateString();
            $payment = $this->recordExpensePayment->execute($lockedExpense, $actor, [
                'amount' => $this->penniesToMoney($remainingPennies),
                'method' => 'bank_transfer',
                'settlement_source' => ExpensePayment::SOURCE_BANK_ACCOUNT,
                'bank_account_id' => $bankAccount->id,
                'reference' => "BANK-TXN-{$lockedTransaction->id}-EXISTING-EXPENSE-{$lockedExpense->id}",
                'paid_at' => $bookingDate,
                'notes' => $notes !== '' ? $notes : null,
                'gbp_exchange_rate' => $expenseCurrency === 'GBP' ? 1 : $lockedExpense->gbp_exchange_rate,
                'exchange_rate_source' => $expenseCurrency === 'GBP' ? 'identity' : 'expense_snapshot',
                'exchange_rate_date' => $bookingDate,
            ]);

            return $this->reconcileTransaction->execute(
                $lockedTransaction,
                $actor,
                [[
                    'candidate_key' => "expense_payment:{$payment->id}",
                    'amount' => $this->penniesToMoney($remainingPennies),
                ]],
                $notes !== '' ? $notes : null,
            );
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

        if (! PermissionHelper::allows($actor, 'manage_finance')
            || ! PermissionHelper::allows($actor, 'post_finance')) {
            throw new DomainException('The actor cannot link and post an expense payment from a bank transaction.');
        }
    }

    private function remainingTransactionPennies(BankTransaction $transaction): int
    {
        $allocated = (float) BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount');

        return max(0, $this->moneyToPennies(abs((float) $transaction->amount) - $allocated));
    }

    private function moneyToPennies(mixed $value): int
    {
        return (int) round((float) $value * 100);
    }

    private function penniesToMoney(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
}
