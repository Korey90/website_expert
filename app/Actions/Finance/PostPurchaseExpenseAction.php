<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\PurchaseJournalPayloadFactory;
use DomainException;

class PostPurchaseExpenseAction
{
    public function __construct(
        private readonly PurchaseJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(Expense $expense, User $actor): JournalEntry
    {
        $expense = Expense::withoutGlobalScopes()->with(['items.taxCode', 'supplier'])->findOrFail($expense->id);

        if ($expense->status !== Expense::STATUS_POSTED) {
            throw new DomainException('Only a posted expense can create a purchase journal.');
        }

        if (! $expense->items->count() || (float) $expense->total <= 0) {
            throw new DomainException('An expense requires items and a positive total before posting.');
        }

        $business = Business::query()->findOrFail($expense->business_id);

        return $this->postJournal->execute($business, $actor, $this->payloadFactory->forExpense($expense));
    }
}
