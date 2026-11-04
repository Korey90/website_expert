<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\PurchaseCreditNote;
use App\Models\User;
use App\Services\Finance\PurchaseJournalPayloadFactory;
use DomainException;

class PostPurchaseCreditNoteAction
{
    public function __construct(
        private readonly PurchaseJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(PurchaseCreditNote $creditNote, User $actor): JournalEntry
    {
        $creditNote = PurchaseCreditNote::withoutGlobalScopes()
            ->with(['items.taxCode', 'items.expenseItem', 'expense'])
            ->findOrFail($creditNote->id);

        if ($creditNote->status !== PurchaseCreditNote::STATUS_ISSUED) {
            throw new DomainException('Only an issued purchase credit note can be posted.');
        }

        if (! $creditNote->items->count() || (float) $creditNote->total <= 0) {
            throw new DomainException('A purchase credit note requires items and a positive total before posting.');
        }

        $business = Business::query()->findOrFail($creditNote->business_id);

        return $this->postJournal->execute($business, $actor, $this->payloadFactory->forCreditNote($creditNote));
    }
}
