<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class RefundInvoicePaymentAction
{
    public function __construct(private readonly PostInvoicePaymentAction $postInvoicePayment) {}

    public function execute(Payment $payment, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $actor): Payment {
            Business::query()->lockForUpdate()->findOrFail($payment->business_id);
            $locked = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);
            $this->assertActorMembership($actor, $locked->business_id);

            if (! in_array($locked->status, ['completed', 'refunded'], true)) {
                throw new DomainException('Only a completed customer payment can be refunded.');
            }

            if ($locked->status === 'completed') {
                $locked->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                ]);
            }

            $this->postInvoicePayment->execute($locked, $actor, true);

            return $locked->fresh(['invoice', 'journalEntries']);
        }, 3);
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the payment business.');
        }
    }
}
