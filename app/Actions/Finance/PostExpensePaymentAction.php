<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\ExpensePayment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\PaymentJournalPayloadFactory;

class PostExpensePaymentAction
{
    public function __construct(
        private readonly PaymentJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(ExpensePayment $payment, User $actor): JournalEntry
    {
        $payment = ExpensePayment::withoutGlobalScopes()->with('expense')->findOrFail($payment->id);
        $business = Business::query()->findOrFail($payment->business_id);

        return $this->postJournal->execute(
            $business,
            $actor,
            $this->payloadFactory->forExpensePayment($payment),
        );
    }
}
