<?php

namespace App\Actions\Finance;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Services\Finance\RealisedFxCalculator;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordExpensePaymentAction
{
    public function __construct(
        private readonly PostExpensePaymentAction $postExpensePayment,
        private readonly RealisedFxCalculator $fxCalculator,
    ) {}

    /** @param array{amount: float|int|string, method: string, bank_account_id?: int|string|null, reference?: string|null, paid_at?: string|null, notes?: string|null, gbp_exchange_rate?: float|int|string|null, exchange_rate_source?: string|null, exchange_rate_date?: string|null} $data */
    public function execute(Expense $expense, User $actor, array $data): ExpensePayment
    {
        return DB::transaction(function () use ($expense, $actor, $data): ExpensePayment {
            Business::query()->lockForUpdate()->findOrFail($expense->business_id);
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);
            $this->assertActorMembership($actor, $locked->business_id);
            $reference = trim((string) ($data['reference'] ?? '')) ?: 'EPAY-'.Str::uuid();
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $bankAccountId = $this->bankAccountId($locked->business_id, $data['bank_account_id'] ?? null);

            $existing = ExpensePayment::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('reference', $reference)
                ->first();

            if ($existing) {
                $requestedRate = filled($data['gbp_exchange_rate'] ?? null)
                    ? round((float) $data['gbp_exchange_rate'], 8)
                    : null;

                if ($existing->expense_id !== $locked->id
                    || round((float) $existing->amount, 2) !== $amount
                    || ($bankAccountId !== null && $existing->bank_account_id !== $bankAccountId)
                    || ($requestedRate !== null && round((float) $existing->gbp_exchange_rate, 8) !== $requestedRate)) {
                    throw new DomainException('The supplier payment reference is already used for different payment data.');
                }

                $this->postExpensePayment->execute($existing, $actor);

                return $existing->fresh(['expense', 'journalEntries']);
            }

            if (! in_array($locked->status, [Expense::STATUS_POSTED, Expense::STATUS_PARTIALLY_PAID], true)
                || ! $locked->journalEntries()->exists()) {
                throw new DomainException('Only a posted outstanding expense can receive a payment.');
            }

            if ($amount <= 0 || $amount > round((float) $locked->amount_due, 2)) {
                throw new DomainException('Payment exceeds the outstanding expense balance.');
            }

            if (blank($data['method'] ?? null)) {
                throw new DomainException('A supplier payment method is required.');
            }

            $currency = strtoupper((string) $locked->currency);
            $carryingRate = $currency === 'GBP' ? 1.0 : (float) $locked->gbp_exchange_rate;
            $providedRate = filled($data['gbp_exchange_rate'] ?? null);
            $settlementRate = $currency === 'GBP'
                ? 1.0
                : (float) ($providedRate ? $data['gbp_exchange_rate'] : $locked->gbp_exchange_rate);
            if ($carryingRate <= 0 || $settlementRate <= 0) {
                throw new DomainException('The expense and payment require positive GBP exchange-rate snapshots.');
            }
            $fx = $this->fxCalculator->calculate(
                $amount,
                $settlementRate,
                $carryingRate,
                RealisedFxCalculator::OUTGOING,
            );

            $payment = ExpensePayment::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                ...$fx,
                'business_id' => $locked->business_id,
                'expense_id' => $locked->id,
                'bank_account_id' => $bankAccountId,
                'created_by' => $actor->id,
                'amount' => $amount,
                'currency' => $currency,
                'gbp_exchange_rate' => $settlementRate,
                'exchange_rate_source' => $currency === 'GBP'
                    ? 'identity'
                    : ($providedRate ? ($data['exchange_rate_source'] ?? 'manual') : ($locked->exchange_rate_source ?: 'expense_snapshot')),
                'exchange_rate_date' => $currency === 'GBP'
                    ? ($data['paid_at'] ?? now())
                    : ($providedRate
                        ? ($data['exchange_rate_date'] ?? $data['paid_at'] ?? now())
                        : ($locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date)),
                'reference' => $reference,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $newPaid = round((float) $locked->amount_paid + $amount, 2);
            $newDue = round((float) $locked->amount_due - $amount, 2);
            $locked->update([
                'amount_paid' => $newPaid,
                'amount_due' => $newDue,
                'status' => $newDue === 0.0 ? Expense::STATUS_PAID : Expense::STATUS_PARTIALLY_PAID,
            ]);

            $this->postExpensePayment->execute($payment, $actor);

            return $payment->fresh(['expense', 'journalEntries']);
        }, 3);
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }
    }

    private function bankAccountId(string $businessId, mixed $bankAccountId): ?int
    {
        if (blank($bankAccountId)) {
            return null;
        }

        $account = BankAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->find($bankAccountId);

        if (! $account) {
            throw new DomainException('The bank account does not belong to the supplier payment business.');
        }

        return (int) $account->id;
    }
}
