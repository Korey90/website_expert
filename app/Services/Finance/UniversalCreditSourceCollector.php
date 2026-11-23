<?php

namespace App\Services\Finance;

use App\Data\Finance\UniversalCreditSourceCandidate;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\FixedAssetTransaction;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class UniversalCreditSourceCollector
{
    /** @return list<UniversalCreditSourceCandidate> */
    public function collect(UniversalCreditAssessmentPeriod $period): array
    {
        $candidates = [
            ...$this->customerPayments($period),
            ...$this->customerRefunds($period),
            ...$this->expensePayments($period),
            ...$this->purchaseCreditRefunds($period),
            ...$this->unreconciledBankAmounts($period),
        ];

        usort($candidates, fn (UniversalCreditSourceCandidate $left, UniversalCreditSourceCandidate $right): int => [
            $left->cashDate->toDateString(),
            $left->source->getMorphClass(),
            (int) $left->source->getKey(),
            $left->eventKey,
        ] <=> [
            $right->cashDate->toDateString(),
            $right->source->getMorphClass(),
            (int) $right->source->getKey(),
            $right->eventKey,
        ]);

        return $candidates;
    }

    /** @return list<UniversalCreditSourceCandidate> */
    private function customerPayments(UniversalCreditAssessmentPeriod $period): array
    {
        return Payment::withoutGlobalScopes()
            ->with(['invoice.items.fixedAssetTransactions'])
            ->where('business_id', $period->business_id)
            ->whereIn('status', ['completed', 'refunded'])
            ->whereDate('paid_at', '>=', $period->date_from)
            ->whereDate('paid_at', '<=', $period->date_to)
            ->whereHas('journalEntries', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get()
            ->map(function (Payment $payment): UniversalCreditSourceCandidate {
                $assetTransactions = $payment->invoice?->items
                    ->flatMap->fixedAssetTransactions
                    ->where('type', FixedAssetTransaction::TYPE_DISPOSAL)
                    ->where('status', FixedAssetTransaction::STATUS_POSTED)
                    ->values() ?? collect();
                $invoiceItems = $payment->invoice?->items ?? collect();
                $allItemsAreAssetDisposals = $invoiceItems->isNotEmpty()
                    && $invoiceItems->every(fn ($item): bool => $item->fixedAssetTransactions
                        ->contains(fn (FixedAssetTransaction $transaction): bool => $transaction->type === FixedAssetTransaction::TYPE_DISPOSAL
                            && $transaction->status === FixedAssetTransaction::STATUS_POSTED));

                return new UniversalCreditSourceCandidate(
                    source: $payment,
                    eventKey: 'receipt',
                    direction: UniversalCreditReportItem::DIRECTION_INCOME,
                    category: $allItemsAreAssetDisposals ? 'previously_expensed_asset_sale' : 'goods_and_services',
                    cashDate: CarbonImmutable::parse($payment->paid_at),
                    documentDate: $this->immutableDate($payment->invoice?->tax_point ?? $payment->invoice?->issue_date),
                    originalAmount: $this->money($payment->amount),
                    originalCurrency: strtoupper((string) $payment->currency),
                    grossAmountPence: $this->gbpPence($payment->amount_gbp, $payment->amount, $payment->currency),
                    context: [
                        'invoice_id' => $payment->invoice_id,
                        'bank_account_id' => $payment->bank_account_id,
                        'payment_method' => $payment->method,
                        'fixed_asset_transaction_ids' => $assetTransactions->pluck('id')->all(),
                        'classification_suggestion_only' => true,
                    ],
                );
            })
            ->all();
    }

    /** @return list<UniversalCreditSourceCandidate> */
    private function customerRefunds(UniversalCreditAssessmentPeriod $period): array
    {
        return Payment::withoutGlobalScopes()
            ->with('invoice')
            ->where('business_id', $period->business_id)
            ->where('status', 'refunded')
            ->whereDate('refunded_at', '>=', $period->date_from)
            ->whereDate('refunded_at', '<=', $period->date_to)
            ->whereHas('journalEntries', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get()
            ->map(fn (Payment $payment): UniversalCreditSourceCandidate => new UniversalCreditSourceCandidate(
                source: $payment,
                eventKey: 'refund',
                direction: UniversalCreditReportItem::DIRECTION_EXPENSE,
                category: 'review_required',
                cashDate: CarbonImmutable::parse($payment->refunded_at),
                documentDate: $this->immutableDate($payment->invoice?->tax_point ?? $payment->invoice?->issue_date),
                originalAmount: $this->money($payment->amount),
                originalCurrency: strtoupper((string) $payment->currency),
                grossAmountPence: $this->gbpPence($payment->amount_gbp, $payment->amount, $payment->currency),
                context: [
                    'invoice_id' => $payment->invoice_id,
                    'bank_account_id' => $payment->bank_account_id,
                    'customer_refund' => true,
                    'classification_suggestion_only' => true,
                ],
            ))
            ->all();
    }

    /** @return list<UniversalCreditSourceCandidate> */
    private function expensePayments(UniversalCreditAssessmentPeriod $period): array
    {
        return ExpensePayment::withoutGlobalScopes()
            ->with(['expense.items.fixedAssetTransactions'])
            ->where('business_id', $period->business_id)
            ->whereDate('paid_at', '>=', $period->date_from)
            ->whereDate('paid_at', '<=', $period->date_to)
            ->whereHas('journalEntries', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get()
            ->map(function (ExpensePayment $payment): UniversalCreditSourceCandidate {
                $items = $payment->expense?->items ?? collect();
                $assetTransactions = $items->flatMap->fixedAssetTransactions
                    ->whereIn('type', [FixedAssetTransaction::TYPE_ACQUISITION, FixedAssetTransaction::TYPE_IMPROVEMENT])
                    ->where('status', FixedAssetTransaction::STATUS_POSTED)
                    ->values();
                $allItemsAreAssets = $items->isNotEmpty()
                    && $items->every(fn ($item): bool => $item->fixedAssetTransactions
                        ->contains(fn (FixedAssetTransaction $transaction): bool => in_array($transaction->type, [
                            FixedAssetTransaction::TYPE_ACQUISITION,
                            FixedAssetTransaction::TYPE_IMPROVEMENT,
                        ], true) && $transaction->status === FixedAssetTransaction::STATUS_POSTED));

                return new UniversalCreditSourceCandidate(
                    source: $payment,
                    eventKey: 'payment',
                    direction: UniversalCreditReportItem::DIRECTION_EXPENSE,
                    category: $allItemsAreAssets ? 'excluded_or_disallowed' : $this->expenseCategory($items),
                    cashDate: CarbonImmutable::parse($payment->paid_at),
                    documentDate: $this->immutableDate($payment->expense?->tax_point ?? $payment->expense?->issue_date),
                    originalAmount: $this->money($payment->amount),
                    originalCurrency: strtoupper((string) $payment->currency),
                    grossAmountPence: $this->gbpPence($payment->amount_gbp, $payment->amount, $payment->currency),
                    context: [
                        'expense_id' => $payment->expense_id,
                        'bank_account_id' => $payment->bank_account_id,
                        'settlement_source' => $payment->settlement_source,
                        'payment_method' => $payment->method,
                        'fixed_asset_transaction_ids' => $assetTransactions->pluck('id')->all(),
                        'classification_suggestion_only' => true,
                    ],
                );
            })
            ->all();
    }

    /** @return list<UniversalCreditSourceCandidate> */
    private function purchaseCreditRefunds(UniversalCreditAssessmentPeriod $period): array
    {
        return PurchaseCreditRefund::withoutGlobalScopes()
            ->with(['expense', 'purchaseCreditNote'])
            ->where('business_id', $period->business_id)
            ->whereDate('received_at', '>=', $period->date_from)
            ->whereDate('received_at', '<=', $period->date_to)
            ->whereHas('journalEntries', fn ($query) => $query->withoutGlobalScopes())
            ->orderBy('id')
            ->get()
            ->map(fn (PurchaseCreditRefund $refund): UniversalCreditSourceCandidate => new UniversalCreditSourceCandidate(
                source: $refund,
                eventKey: 'supplier_refund',
                direction: UniversalCreditReportItem::DIRECTION_INCOME,
                category: 'review_required',
                cashDate: CarbonImmutable::parse($refund->received_at),
                documentDate: $this->immutableDate($refund->purchaseCreditNote?->tax_point ?? $refund->purchaseCreditNote?->issue_date),
                originalAmount: $this->money($refund->amount),
                originalCurrency: strtoupper((string) $refund->currency),
                grossAmountPence: $this->gbpPence($refund->amount_gbp, $refund->amount, $refund->currency),
                context: [
                    'expense_id' => $refund->expense_id,
                    'purchase_credit_note_id' => $refund->purchase_credit_note_id,
                    'supplier_refund' => true,
                    'classification_suggestion_only' => true,
                ],
            ))
            ->all();
    }

    /** @return list<UniversalCreditSourceCandidate> */
    private function unreconciledBankAmounts(UniversalCreditAssessmentPeriod $period): array
    {
        $transactions = BankTransaction::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->whereDate('booking_date', '>=', $period->date_from)
            ->whereDate('booking_date', '<=', $period->date_to)
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $allocated = BankReconciliationAllocation::withoutGlobalScopes()
            ->selectRaw('bank_transaction_id, SUM(amount) as allocated_amount')
            ->where('business_id', $period->business_id)
            ->whereIn('bank_transaction_id', $transactions->pluck('id'))
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->groupBy('bank_transaction_id')
            ->pluck('allocated_amount', 'bank_transaction_id');

        return $transactions
            ->map(function (BankTransaction $transaction) use ($allocated): ?UniversalCreditSourceCandidate {
                $totalPence = abs($this->pence($transaction->amount));
                $allocatedPence = abs($this->pence($allocated->get($transaction->id, '0.00')));
                $remainingPence = max(0, $totalPence - $allocatedPence);

                if ($remainingPence === 0) {
                    return null;
                }

                $direction = (float) $transaction->amount >= 0
                    ? UniversalCreditReportItem::DIRECTION_INCOME
                    : UniversalCreditReportItem::DIRECTION_EXPENSE;
                $currency = strtoupper((string) $transaction->currency);

                return new UniversalCreditSourceCandidate(
                    source: $transaction,
                    eventKey: 'unreconciled',
                    direction: $direction,
                    category: 'review_required',
                    cashDate: CarbonImmutable::parse($transaction->booking_date),
                    documentDate: $this->immutableDate($transaction->value_date),
                    originalAmount: $this->fromPence($remainingPence),
                    originalCurrency: $currency,
                    grossAmountPence: $currency === 'GBP' ? $remainingPence : null,
                    context: [
                        'bank_account_id' => $transaction->bank_account_id,
                        'bank_statement_import_id' => $transaction->bank_statement_import_id,
                        'unreconciled_original_amount' => $this->fromPence($remainingPence),
                        'classification_suggestion_only' => true,
                    ],
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    private function expenseCategory(Collection $items): string
    {
        $categories = $items->pluck('category')->filter()->unique()->values();

        if ($categories->count() !== 1) {
            return 'review_required';
        }

        return match ($categories->first()) {
            'cost_of_sales' => 'goods_materials_and_work_clothing',
            'software' => 'subscriptions_and_training',
            'office' => 'office_and_equipment',
            'travel' => 'other_vehicle_and_business_travel',
            'professional_fees', 'insurance', 'bank_fees' => 'legal_financial_and_insurance',
            'marketing' => 'marketing_and_advertising',
            'rent' => 'place_of_business',
            default => 'review_required',
        };
    }

    private function gbpPence(mixed $amountGbp, mixed $originalAmount, mixed $currency): ?int
    {
        if ($amountGbp !== null) {
            return abs($this->pence($amountGbp));
        }

        return strtoupper((string) $currency) === 'GBP'
            ? abs($this->pence($originalAmount))
            : null;
    }

    private function pence(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function fromPence(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    private function money(mixed $amount): string
    {
        return number_format(abs((float) $amount), 2, '.', '');
    }

    private function immutableDate(mixed $date): ?CarbonImmutable
    {
        return $date === null ? null : CarbonImmutable::parse($date);
    }
}
