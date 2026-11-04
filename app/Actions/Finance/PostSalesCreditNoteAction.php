<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\CreditNote;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\SalesJournalPayloadFactory;
use DomainException;

class PostSalesCreditNoteAction
{
    public function __construct(
        private readonly SalesJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(CreditNote $creditNote, User $actor): JournalEntry
    {
        $creditNote = CreditNote::withoutGlobalScopes()->with(['items', 'invoice'])->findOrFail($creditNote->id);

        if (! in_array($creditNote->status, [
            CreditNote::STATUS_ISSUED,
            CreditNote::STATUS_PARTIALLY_ALLOCATED,
            CreditNote::STATUS_ALLOCATED,
        ], true)) {
            throw new DomainException('Only an issued sales credit note can be posted.');
        }

        if (! $creditNote->items->count() || (float) $creditNote->total <= 0) {
            throw new DomainException('A sales credit note requires items and a positive total before posting.');
        }

        $business = Business::query()->findOrFail($creditNote->business_id);

        return $this->postJournal->execute($business, $actor, $this->payloadFactory->forCreditNote($creditNote));
    }
}
