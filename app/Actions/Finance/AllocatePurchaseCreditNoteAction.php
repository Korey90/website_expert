<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\PurchaseCreditNote;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AllocatePurchaseCreditNoteAction
{
    public function execute(PurchaseCreditNote $creditNote, User $actor, float $amount): PurchaseCreditNote
    {
        return DB::transaction(function () use ($creditNote, $actor, $amount): PurchaseCreditNote {
            $locked = PurchaseCreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);
            $this->assertSettleable($locked, $actor);
            $expense = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->expense_id);
            $amount = round($amount, 2);

            if ($amount <= 0 || $amount > $locked->remainingAmount() || $amount > round((float) $expense->amount_due, 2)) {
                throw new DomainException('Allocation exceeds the remaining credit or expense balance.');
            }

            $newAllocated = round((float) $locked->allocated_amount + $amount, 2);
            $remaining = round((float) $locked->total - $newAllocated - (float) $locked->refunded_amount, 2);
            $locked->update([
                'allocated_amount' => $newAllocated,
                'allocated_by' => $actor->id,
                'allocated_at' => $remaining === 0.0 ? now() : null,
                'status' => $remaining === 0.0
                    ? PurchaseCreditNote::STATUS_SETTLED
                    : PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
            ]);
            $expense->update([
                'amount_due' => round((float) $expense->amount_due - $amount, 2),
            ]);

            return $locked->fresh(['expense', 'refunds']);
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
            throw new DomainException('Only an issued purchase credit note can be allocated.');
        }
    }
}
