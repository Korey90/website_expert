<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidExpenseAction
{
    public function execute(Expense $expense, User $actor, string $reason): Expense
    {
        return DB::transaction(function () use ($expense, $actor, $reason): Expense {
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);

            $this->assertActorMembership($locked, $actor);

            if (! in_array($locked->status, [Expense::STATUS_DRAFT, Expense::STATUS_APPROVED], true)) {
                throw new DomainException('Only a draft or approved expense can be voided before ledger posting.');
            }

            if (blank($reason)) {
                throw new DomainException('A reason is required to void an expense.');
            }

            $locked->update([
                'status' => Expense::STATUS_VOID,
                'voided_by' => $actor->id,
                'void_reason' => trim($reason),
                'voided_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    private function assertActorMembership(Expense $expense, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $expense->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }
    }
}
