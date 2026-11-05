<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordPurchaseCreditRefundAction
{
    public function __construct(private readonly PostPurchaseCreditRefundAction $postPurchaseCreditRefund) {}

    /** @param array{amount: float|int|string, method: string, reference?: string|null, received_at?: string, notes?: string|null} $data */
    public function execute(PurchaseCreditNote $creditNote, User $actor, array $data): PurchaseCreditRefund
    {
        return DB::transaction(function () use ($creditNote, $actor, $data): PurchaseCreditRefund {
            Business::query()->lockForUpdate()->findOrFail($creditNote->business_id);
            $locked = PurchaseCreditNote::withoutGlobalScopes()->lockForUpdate()->findOrFail($creditNote->id);
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $reference = trim((string) ($data['reference'] ?? '')) ?: 'PCR-'.Str::uuid();
            $existing = PurchaseCreditRefund::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('reference', $reference)
                ->first();

            if ($existing) {
                if ($existing->purchase_credit_note_id !== $locked->id || round((float) $existing->amount, 2) !== $amount) {
                    throw new DomainException('The supplier refund reference is already used for different refund data.');
                }

                $this->postPurchaseCreditRefund->execute($existing, $actor);

                return $existing->fresh(['purchaseCreditNote', 'expense', 'journalEntries']);
            }

            $this->assertSettleable($locked, $actor);
            $expense = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->expense_id);
            $alreadyRefundedForExpense = (float) PurchaseCreditRefund::withoutGlobalScopes()
                ->where('expense_id', $expense->id)
                ->sum('amount');
            $refundableCash = round((float) $expense->amount_paid - $alreadyRefundedForExpense, 2);

            if ($amount <= 0 || $amount > $locked->remainingAmount()) {
                throw new DomainException('Refund exceeds the remaining purchase credit balance.');
            }

            if ($amount > $refundableCash) {
                throw new DomainException('Refund exceeds the amount paid for the expense.');
            }

            if (blank($data['method'] ?? null)) {
                throw new DomainException('A refund method is required.');
            }

            $currency = strtoupper((string) $locked->currency);
            $rate = $currency === 'GBP' ? 1.0 : (float) $locked->gbp_exchange_rate;
            if ($rate <= 0) {
                throw new DomainException('The purchase credit requires a GBP exchange-rate snapshot before refund.');
            }

            $refund = PurchaseCreditRefund::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                'business_id' => $locked->business_id,
                'purchase_credit_note_id' => $locked->id,
                'expense_id' => $expense->id,
                'received_by' => $actor->id,
                'amount' => $amount,
                'amount_gbp' => round($amount * $rate, 2),
                'currency' => $currency,
                'gbp_exchange_rate' => $rate,
                'exchange_rate_source' => $currency === 'GBP' ? 'identity' : ($locked->exchange_rate_source ?: 'purchase_credit_snapshot'),
                'exchange_rate_date' => $locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date,
                'received_at' => $data['received_at'] ?? today(),
                'reference' => $reference,
            ]);

            $newRefunded = round((float) $locked->refunded_amount + $amount, 2);
            $remaining = round((float) $locked->total - (float) $locked->allocated_amount - $newRefunded, 2);
            $locked->update([
                'refunded_amount' => $newRefunded,
                'status' => $remaining === 0.0
                    ? PurchaseCreditNote::STATUS_SETTLED
                    : PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
            ]);

            $this->postPurchaseCreditRefund->execute($refund, $actor);

            return $refund->fresh(['purchaseCreditNote', 'expense', 'journalEntries']);
        }, 3);
    }

    private function assertSettleable(PurchaseCreditNote $creditNote, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $creditNote->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the purchase credit note business.');
        }

        if (! in_array($creditNote->status, [
            PurchaseCreditNote::STATUS_ISSUED,
            PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
        ], true)) {
            throw new DomainException('Only an issued purchase credit note can receive a refund.');
        }
    }
}
