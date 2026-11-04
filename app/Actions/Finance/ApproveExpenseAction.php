<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveExpenseAction
{
    public function execute(Expense $expense, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $actor): Expense {
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);

            $this->assertActorMembership($locked, $actor);

            if ($locked->status !== Expense::STATUS_DRAFT) {
                throw new DomainException('Only a draft expense can be approved.');
            }

            if (! $locked->items()->exists() || (float) $locked->total <= 0 || blank($locked->business_purpose)) {
                throw new DomainException('An expense requires items, a positive total and a business purpose before approval.');
            }

            $locked->update([
                'status' => Expense::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
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
