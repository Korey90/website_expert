<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use App\Services\Finance\PaymentJournalPayloadFactory;

class PostPurchaseCreditRefundAction
{
    public function __construct(
        private readonly PaymentJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(PurchaseCreditRefund $refund, User $actor): JournalEntry
    {
        $refund = PurchaseCreditRefund::withoutGlobalScopes()
            ->with(['expense', 'purchaseCreditNote'])
            ->findOrFail($refund->id);
        $business = Business::query()->findOrFail($refund->business_id);

        return $this->postJournal->execute(
            $business,
            $actor,
            $this->payloadFactory->forPurchaseCreditRefund($refund),
        );
    }
}
