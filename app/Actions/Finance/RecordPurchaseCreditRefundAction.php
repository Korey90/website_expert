<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\User;
use App\Services\Finance\RealisedFxCalculator;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordPurchaseCreditRefundAction
{
    public function __construct(
        private readonly PostPurchaseCreditRefundAction $postPurchaseCreditRefund,
        private readonly RealisedFxCalculator $fxCalculator,
    ) {}

    /** @param array{amount: float|int|string, method: string, reference?: string|null, received_at?: string, notes?: string|null, gbp_exchange_rate?: float|int|string|null, exchange_rate_source?: string|null, exchange_rate_date?: string|null} $data */
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
                $requestedRate = filled($data['gbp_exchange_rate'] ?? null)
                    ? round((float) $data['gbp_exchange_rate'], 8)
                    : null;

                if ($existing->purchase_credit_note_id !== $locked->id
                    || round((float) $existing->amount, 2) !== $amount
                    || ($requestedRate !== null && round((float) $existing->gbp_exchange_rate, 8) !== $requestedRate)) {
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
            $carryingRate = $currency === 'GBP' ? 1.0 : (float) $locked->gbp_exchange_rate;
            $providedRate = filled($data['gbp_exchange_rate'] ?? null);
            $settlementRate = $currency === 'GBP'
                ? 1.0
                : (float) ($providedRate ? $data['gbp_exchange_rate'] : $locked->gbp_exchange_rate);
            if ($carryingRate <= 0 || $settlementRate <= 0) {
                throw new DomainException('The purchase credit and refund require positive GBP exchange-rate snapshots.');
            }
            $fx = $this->fxCalculator->calculate(
                $amount,
                $settlementRate,
                $carryingRate,
                RealisedFxCalculator::INCOMING,
            );

            $refund = PurchaseCreditRefund::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                ...$fx,
                'business_id' => $locked->business_id,
                'purchase_credit_note_id' => $locked->id,
                'expense_id' => $expense->id,
                'received_by' => $actor->id,
                'amount' => $amount,
                'currency' => $currency,
                'gbp_exchange_rate' => $settlementRate,
                'exchange_rate_source' => $currency === 'GBP'
                    ? 'identity'
                    : ($providedRate ? ($data['exchange_rate_source'] ?? 'manual') : ($locked->exchange_rate_source ?: 'purchase_credit_snapshot')),
                'exchange_rate_date' => $currency === 'GBP'
                    ? ($data['received_at'] ?? today())
                    : ($providedRate
                        ? ($data['exchange_rate_date'] ?? $data['received_at'] ?? today())
                        : ($locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date)),
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
