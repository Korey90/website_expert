<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class IssueInvoiceAction
{
    public function __construct(private readonly PostSalesInvoiceAction $postSalesInvoice) {}

    public function execute(Invoice $invoice, User $actor): Invoice
    {
        return DB::transaction(function () use ($invoice, $actor): Invoice {
            Business::query()->lockForUpdate()->findOrFail($invoice->business_id);
            $locked = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($locked->status, ['draft', 'sent'], true)) {
                throw new DomainException('Only a draft invoice can be issued.');
            }

            if ($locked->status === 'draft') {
                if (! $locked->items()->exists()) {
                    throw new DomainException('An invoice requires at least one item before issue.');
                }

                $locked->recalculate();
                $locked->refresh();

                if ((float) $locked->total <= 0) {
                    throw new DomainException('An invoice requires a positive total before issue.');
                }

                $locked->update(['status' => 'sent', 'sent_at' => now()]);
            }

            $this->postSalesInvoice->execute($locked, $actor);

            return $locked->fresh();
        }, 3);
    }
}
