<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostExpenseAction
{
    public function __construct(private readonly PostPurchaseExpenseAction $postPurchaseExpense) {}

    public function execute(Expense $expense, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $actor): Expense {
            Business::query()->lockForUpdate()->findOrFail($expense->business_id);
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);

            $this->assertActorMembership($locked, $actor);

            if (! in_array($locked->status, [Expense::STATUS_APPROVED, Expense::STATUS_POSTED], true)) {
                throw new DomainException('Only an approved expense can be posted.');
            }

            if ($locked->status === Expense::STATUS_APPROVED) {
                $locked->update([
                    'status' => Expense::STATUS_POSTED,
                    'posted_by' => $actor->id,
                    'posted_at' => now(),
                ]);
            }

            $this->postPurchaseExpense->execute($locked, $actor);

            return $locked->fresh();
        }, 3);
    }

    private function assertActorMembership(Expense $expense, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $expense->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }
    }
}
