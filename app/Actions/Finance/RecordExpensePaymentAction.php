<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordExpensePaymentAction
{
    public function __construct(private readonly PostExpensePaymentAction $postExpensePayment) {}

    /** @param array{amount: float|int|string, method: string, reference?: string|null, paid_at?: string|null, notes?: string|null} $data */
    public function execute(Expense $expense, User $actor, array $data): ExpensePayment
    {
        return DB::transaction(function () use ($expense, $actor, $data): ExpensePayment {
            Business::query()->lockForUpdate()->findOrFail($expense->business_id);
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);
            $this->assertActorMembership($actor, $locked->business_id);
            $reference = trim((string) ($data['reference'] ?? '')) ?: 'EPAY-'.Str::uuid();
            $amount = round((float) ($data['amount'] ?? 0), 2);

            $existing = ExpensePayment::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('reference', $reference)
                ->first();

            if ($existing) {
                if ($existing->expense_id !== $locked->id || round((float) $existing->amount, 2) !== $amount) {
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
            $rate = $currency === 'GBP' ? 1.0 : (float) $locked->gbp_exchange_rate;
            if ($rate <= 0) {
                throw new DomainException('The expense requires a GBP exchange-rate snapshot before payment.');
            }

            $payment = ExpensePayment::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                'business_id' => $locked->business_id,
                'expense_id' => $locked->id,
                'created_by' => $actor->id,
                'amount' => $amount,
                'currency' => $currency,
                'amount_gbp' => round($amount * $rate, 2),
                'gbp_exchange_rate' => $rate,
                'exchange_rate_source' => $currency === 'GBP' ? 'identity' : ($locked->exchange_rate_source ?: 'expense_snapshot'),
                'exchange_rate_date' => $locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date,
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
}
