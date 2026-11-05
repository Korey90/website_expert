<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Finance\PaymentJournalPayloadFactory;
use DomainException;

class PostInvoicePaymentAction
{
    public function __construct(
        private readonly PaymentJournalPayloadFactory $payloadFactory,
        private readonly PostJournalEntryAction $postJournal,
    ) {}

    public function execute(Payment $payment, User $actor, bool $refund = false): JournalEntry
    {
        $payment = Payment::withoutGlobalScopes()->with('invoice')->findOrFail($payment->id);
        $expectedStatus = $refund ? 'refunded' : 'completed';

        if ($payment->status !== $expectedStatus) {
            throw new DomainException("Only a {$expectedStatus} customer payment can be posted for this event.");
        }

        if ($refund && ! $payment->journalEntries()->where('idempotency_key', "invoice_payment:{$payment->id}:receipt")->exists()) {
            throw new DomainException('A customer payment must be posted before it can be refunded.');
        }

        $business = Business::query()->findOrFail($payment->business_id);

        return $this->postJournal->execute(
            $business,
            $actor,
            $this->payloadFactory->forInvoicePayment($payment, $refund),
        );
    }
}
