<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\SalesJournalPayloadFactory;
use DomainException;

class PostSalesInvoiceAction
{
    public function __construct(
        private readonly SalesJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(Invoice $invoice, User $actor): JournalEntry
    {
        $invoice = Invoice::withoutGlobalScopes()->with('items')->findOrFail($invoice->id);

        if (! in_array($invoice->status, ['sent', 'partially_paid', 'paid', 'overdue'], true)) {
            throw new DomainException('Only an issued sales invoice can be posted.');
        }

        if (! $invoice->items->count() || (float) $invoice->total <= 0) {
            throw new DomainException('A sales invoice requires items and a positive total before posting.');
        }

        $business = Business::query()->findOrFail($invoice->business_id);

        return $this->postJournal->execute($business, $actor, $this->payloadFactory->forInvoice($invoice));
    }
}
