<?php

namespace App\Actions\Finance;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class SynchronizeInvoiceSettlementAction
{
    public function execute(int $invoiceId): ?Invoice
    {
        return DB::transaction(function () use ($invoiceId): ?Invoice {
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->find($invoiceId);

            if (! $invoice) {
                return null;
            }

            $invoice->recalculate();
            $invoice->refresh();

            if (! in_array($invoice->status, ['sent', 'overdue', 'partially_paid', 'paid'], true)) {
                return $invoice;
            }

            $total = round((float) $invoice->total, 2);
            $amountDue = round((float) $invoice->amount_due, 2);
            $settled = round($total - $amountDue, 2);

            if ($total > 0 && $amountDue <= 0) {
                $status = 'paid';
            } elseif ($settled > 0) {
                $status = 'partially_paid';
            } elseif (in_array($invoice->status, ['partially_paid', 'paid'], true)) {
                $status = 'sent';
            } else {
                $status = $invoice->status;
            }

            $invoice->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? ($invoice->paid_at ?? now()) : null,
            ]);

            return $invoice->fresh();
        });
    }
}
