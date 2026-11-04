<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RecordPurchaseCreditRefundAction
{
    /** @param array{amount: float|int|string, method: string, reference?: string|null, received_at?: string, notes?: string|null} $data */
    public function execute(PurchaseCreditNote $creditNote, User $actor, array $data): PurchaseCreditRefund
    {
        return DB::transaction(function () use ($creditNote, $actor, $data): PurchaseCreditRefund {
            $locked = PurchaseCreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);
            $this->assertSettleable($locked, $actor);
            $expense = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->expense_id);
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $alreadyRefundedForExpense = (float) PurchaseCreditRefund::withoutGlobalScopes()
                ->where('expense_id', $expense->id)
                ->sum('amount');
            $refundableCash = round((float) $expense->amount_paid - $alreadyRefundedForExpense, 2);

            if ($amount <= 0 || $amount > $locked->remainingAmount()) {
                throw new DomainException('Refund exceeds the remaining purchase credit balance.');
            }

            if ($amount > $refundableCash) {
                throw new DomainException('Refund exceeds the amount paid for the expense.');
            }

            if (blank($data['method'] ?? null)) {
                throw new DomainException('A refund method is required.');
            }

            $refund = PurchaseCreditRefund::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'reference', 'notes']),
                'business_id' => $locked->business_id,
                'purchase_credit_note_id' => $locked->id,
                'expense_id' => $expense->id,
                'received_by' => $actor->id,
                'amount' => $amount,
                'currency' => $locked->currency,
                'received_at' => $data['received_at'] ?? today(),
            ]);

            $newRefunded = round((float) $locked->refunded_amount + $amount, 2);
            $remaining = round((float) $locked->total - (float) $locked->allocated_amount - $newRefunded, 2);
            $locked->update([
                'refunded_amount' => $newRefunded,
                'status' => $remaining === 0.0
                    ? PurchaseCreditNote::STATUS_SETTLED
                    : PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
            ]);

            return $refund->fresh(['purchaseCreditNote', 'expense']);
        });
    }

    private function assertSettleable(PurchaseCreditNote $creditNote, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $creditNote->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the purchase credit note business.');
        }

        if (! in_array($creditNote->status, [
            PurchaseCreditNote::STATUS_ISSUED,
            PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
        ], true)) {
            throw new DomainException('Only an issued purchase credit note can receive a refund.');
        }
    }
}
