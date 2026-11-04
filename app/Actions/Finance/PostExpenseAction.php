<?php

namespace App\Actions\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Services\Finance\AccountingPeriodGuard;
use DomainException;
use Illuminate\Support\Facades\DB;

class PostExpenseAction
{
    public function __construct(private readonly AccountingPeriodGuard $periodGuard) {}

    public function execute(Expense $expense, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $actor): Expense {
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);

            $this->assertActorMembership($locked, $actor);

            if ($locked->status !== Expense::STATUS_APPROVED) {
                throw new DomainException('Only an approved expense can be posted.');
            }

            $this->periodGuard->assertDateIsWritable(
                $locked->business_id,
                ($locked->tax_point ?? $locked->issue_date)->toDateString(),
            );

            $locked->update([
                'status' => Expense::STATUS_POSTED,
                'posted_by' => $actor->id,
                'posted_at' => now(),
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
