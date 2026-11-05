<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordInvoicePaymentAction
{
    public function __construct(private readonly PostInvoicePaymentAction $postInvoicePayment) {}

    /** @param array{amount: float|int|string, method: string, reference?: string|null, paid_at?: string|null, notes?: string|null} $data */
    public function execute(Invoice $invoice, User $actor, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $actor, $data): Payment {
            Business::query()->lockForUpdate()->findOrFail($invoice->business_id);
            $locked = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);
            $this->assertActorMembership($actor, $locked->business_id);
            $reference = trim((string) ($data['reference'] ?? '')) ?: 'PAY-'.Str::uuid();
            $amount = round((float) ($data['amount'] ?? 0), 2);

            $existing = Payment::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('reference', $reference)
                ->first();

            if ($existing) {
                if ($existing->invoice_id !== $locked->id || round((float) $existing->amount, 2) !== $amount || $existing->status !== 'completed') {
                    throw new DomainException('The payment reference is already used for different payment data.');
                }

                $this->postInvoicePayment->execute($existing, $actor);

                return $existing->fresh(['invoice', 'journalEntries']);
            }

            if (! in_array($locked->status, ['sent', 'partially_paid', 'overdue'], true)
                || ! $locked->journalEntries()->exists()) {
                throw new DomainException('Only a posted outstanding invoice can receive a payment.');
            }

            if ($amount <= 0 || $amount > round((float) $locked->amount_due, 2)) {
                throw new DomainException('Payment exceeds the outstanding invoice balance.');
            }

            if (blank($data['method'] ?? null)) {
                throw new DomainException('A payment method is required.');
            }

            $snapshot = $this->snapshot(
                $locked->currency,
                $amount,
                $locked->gbp_exchange_rate,
                $locked->exchange_rate_source,
                $locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date,
            );
            $payment = Payment::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                ...$snapshot,
                'business_id' => $locked->business_id,
                'invoice_id' => $locked->id,
                'created_by' => $actor->id,
                'amount' => $amount,
                'currency' => strtoupper((string) $locked->currency),
                'status' => 'completed',
                'reference' => $reference,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $this->postInvoicePayment->execute($payment, $actor);

            return $payment->fresh(['invoice', 'journalEntries']);
        }, 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(string $currency, float $amount, mixed $rate, mixed $source, mixed $date): array
    {
        $currency = strtoupper($currency);
        $effectiveRate = $currency === 'GBP' ? 1.0 : (float) $rate;

        if ($effectiveRate <= 0) {
            throw new DomainException('The invoice requires a GBP exchange-rate snapshot before payment.');
        }

        return [
            'amount_gbp' => round($amount * $effectiveRate, 2),
            'gbp_exchange_rate' => $effectiveRate,
            'exchange_rate_source' => $currency === 'GBP' ? 'identity' : ($source ?: 'invoice_snapshot'),
            'exchange_rate_date' => $date,
        ];
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the invoice business.');
        }
    }
}
