<?php

namespace App\Observers;

use App\Actions\Finance\SynchronizeInvoiceSettlementAction;
use App\Models\Payment;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        $this->synchronize($payment->invoice_id);

        $originalInvoiceId = $payment->getOriginal('invoice_id');

        if ($originalInvoiceId && (int) $originalInvoiceId !== (int) $payment->invoice_id) {
            $this->synchronize((int) $originalInvoiceId);
        }
    }

    public function deleted(Payment $payment): void
    {
        $this->synchronize($payment->invoice_id);
    }

    private function synchronize(?int $invoiceId): void
    {
        if ($invoiceId) {
            app(SynchronizeInvoiceSettlementAction::class)->execute($invoiceId);
        }
    }
}
