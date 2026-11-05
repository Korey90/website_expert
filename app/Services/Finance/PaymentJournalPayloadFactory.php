<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\ExpensePayment;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use DomainException;

class PaymentJournalPayloadFactory
{
    /** @return array<string, mixed> */
    public function forInvoicePayment(Payment $payment, bool $refund = false): array
    {
        $payment->loadMissing('invoice');
        $business = Business::query()->findOrFail($payment->business_id);
        $bank = $this->accountId($business, '1100');
        $receivable = $this->accountId($business, '1200');
        $amountGbp = $this->requiredGbpAmount($payment->amount_gbp, 'customer payment');
        $carryingAmountGbp = $this->requiredGbpAmount(
            $payment->carrying_amount_gbp ?? $payment->amount_gbp,
            'customer payment carrying amount',
        );
        $realisedFxGbp = $this->realisedFx($payment->realised_fx_gbp);
        $dimensions = [
            'client_id' => $payment->invoice?->client_id,
            'project_id' => $payment->invoice?->project_id,
        ];
        $settlementCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $payment->currency),
            'original_amount' => $payment->amount,
            'exchange_rate' => $this->exchangeRate($payment->currency, $payment->gbp_exchange_rate),
        ];
        $carryingCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $payment->currency),
            'original_amount' => $payment->amount,
            'exchange_rate' => $this->exchangeRate($payment->currency, $payment->invoice?->gbp_exchange_rate),
        ];

        $lines = $refund
            ? [
                [...$carryingCommon, 'ledger_account_id' => $receivable, 'debit_gbp' => $carryingAmountGbp, 'credit_gbp' => '0.00', 'memo' => "Customer refund {$payment->reference}"],
                [...$settlementCommon, 'ledger_account_id' => $bank, 'debit_gbp' => '0.00', 'credit_gbp' => $amountGbp, 'memo' => "Bank refund {$payment->reference}"],
            ]
            : [
                [...$settlementCommon, 'ledger_account_id' => $bank, 'debit_gbp' => $amountGbp, 'credit_gbp' => '0.00', 'memo' => "Bank receipt {$payment->reference}"],
                [...$carryingCommon, 'ledger_account_id' => $receivable, 'debit_gbp' => '0.00', 'credit_gbp' => $carryingAmountGbp, 'memo' => "Customer payment {$payment->reference}"],
            ];

        $this->appendFxLine(
            $lines,
            $business,
            $realisedFxGbp,
            ['client_id' => $payment->invoice?->client_id, 'project_id' => $payment->invoice?->project_id],
            "Customer payment FX {$payment->reference}",
            $refund,
        );

        $payload = [
            'entry_date' => ($refund ? $payment->refunded_at : $payment->paid_at)->toDateString(),
            'description' => ($refund ? 'Customer refund for ' : 'Customer payment for ').$payment->invoice?->number,
            'idempotency_key' => 'invoice_payment:'.$payment->id.($refund ? ':refund' : ':receipt'),
            'source' => $payment,
            'source_snapshot' => $this->paymentSnapshot($payment, $refund ? 'refund' : 'receipt'),
            'lines' => $lines,
        ];

        return $payload;
    }

    /** @return array<string, mixed> */
    public function forExpensePayment(ExpensePayment $payment): array
    {
        $payment->loadMissing('expense');
        $business = Business::query()->findOrFail($payment->business_id);
        $bank = $this->accountId($business, '1100');
        $payable = $this->accountId($business, '2100');
        $amountGbp = $this->requiredGbpAmount($payment->amount_gbp, 'supplier payment');
        $carryingAmountGbp = $this->requiredGbpAmount(
            $payment->carrying_amount_gbp ?? $payment->amount_gbp,
            'supplier payment carrying amount',
        );
        $realisedFxGbp = $this->realisedFx($payment->realised_fx_gbp);
        $dimensions = [
            'supplier_id' => $payment->expense?->supplier_id,
            'client_id' => $payment->expense?->client_id,
            'project_id' => $payment->expense?->project_id,
        ];
        $settlementCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $payment->currency),
            'original_amount' => $payment->amount,
            'exchange_rate' => $this->exchangeRate($payment->currency, $payment->gbp_exchange_rate),
        ];
        $carryingCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $payment->currency),
            'original_amount' => $payment->amount,
            'exchange_rate' => $this->exchangeRate($payment->currency, $payment->expense?->gbp_exchange_rate),
        ];

        $payload = [
            'entry_date' => $payment->paid_at->toDateString(),
            'description' => 'Supplier payment for '.($payment->expense?->supplier_invoice_number ?: "expense #{$payment->expense_id}"),
            'idempotency_key' => "expense_payment:{$payment->id}:payment",
            'source' => $payment,
            'source_snapshot' => [
                'id' => $payment->id,
                'expense_id' => $payment->expense_id,
                'reference' => $payment->reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'amount_gbp' => $payment->amount_gbp,
                'gbp_exchange_rate' => $payment->gbp_exchange_rate,
                'paid_at' => $payment->paid_at->toISOString(),
                ...$this->fxSnapshot($payment->carrying_amount_gbp, $realisedFxGbp),
            ],
            'lines' => [
                [...$carryingCommon, 'ledger_account_id' => $payable, 'debit_gbp' => $carryingAmountGbp, 'credit_gbp' => '0.00', 'memo' => "Supplier payment {$payment->reference}"],
                [...$settlementCommon, 'ledger_account_id' => $bank, 'debit_gbp' => '0.00', 'credit_gbp' => $amountGbp, 'memo' => "Bank payment {$payment->reference}"],
            ],
        ];

        $this->appendFxLine(
            $payload['lines'],
            $business,
            $realisedFxGbp,
            [
                'supplier_id' => $payment->expense?->supplier_id,
                'client_id' => $payment->expense?->client_id,
                'project_id' => $payment->expense?->project_id,
            ],
            "Supplier payment FX {$payment->reference}",
        );

        return $payload;
    }

    /** @return array<string, mixed> */
    public function forPurchaseCreditRefund(PurchaseCreditRefund $refund): array
    {
        $refund->loadMissing(['expense', 'purchaseCreditNote']);
        $business = Business::query()->findOrFail($refund->business_id);
        $bank = $this->accountId($business, '1100');
        $payable = $this->accountId($business, '2100');
        $amountGbp = $this->requiredGbpAmount($refund->amount_gbp, 'supplier refund');
        $carryingAmountGbp = $this->requiredGbpAmount(
            $refund->carrying_amount_gbp ?? $refund->amount_gbp,
            'supplier refund carrying amount',
        );
        $realisedFxGbp = $this->realisedFx($refund->realised_fx_gbp);
        $dimensions = [
            'supplier_id' => $refund->purchaseCreditNote?->supplier_id,
            'client_id' => $refund->expense?->client_id,
            'project_id' => $refund->expense?->project_id,
        ];
        $settlementCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $refund->currency),
            'original_amount' => $refund->amount,
            'exchange_rate' => $this->exchangeRate($refund->currency, $refund->gbp_exchange_rate),
        ];
        $carryingCommon = [
            ...$dimensions,
            'original_currency' => strtoupper((string) $refund->currency),
            'original_amount' => $refund->amount,
            'exchange_rate' => $this->exchangeRate($refund->currency, $refund->purchaseCreditNote?->gbp_exchange_rate),
        ];

        $payload = [
            'entry_date' => $refund->received_at->toDateString(),
            'description' => 'Supplier refund for '.$refund->purchaseCreditNote?->number,
            'idempotency_key' => "purchase_credit_refund:{$refund->id}:receipt",
            'source' => $refund,
            'source_snapshot' => [
                'id' => $refund->id,
                'purchase_credit_note_id' => $refund->purchase_credit_note_id,
                'expense_id' => $refund->expense_id,
                'reference' => $refund->reference,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'amount_gbp' => $refund->amount_gbp,
                'gbp_exchange_rate' => $refund->gbp_exchange_rate,
                'received_at' => $refund->received_at->toDateString(),
                ...$this->fxSnapshot($refund->carrying_amount_gbp, $realisedFxGbp),
            ],
            'lines' => [
                [...$settlementCommon, 'ledger_account_id' => $bank, 'debit_gbp' => $amountGbp, 'credit_gbp' => '0.00', 'memo' => "Supplier refund {$refund->reference}"],
                [...$carryingCommon, 'ledger_account_id' => $payable, 'debit_gbp' => '0.00', 'credit_gbp' => $carryingAmountGbp, 'memo' => "Purchase credit refund {$refund->reference}"],
            ],
        ];

        $this->appendFxLine(
            $payload['lines'],
            $business,
            $realisedFxGbp,
            [
                'supplier_id' => $refund->purchaseCreditNote?->supplier_id,
                'client_id' => $refund->expense?->client_id,
                'project_id' => $refund->expense?->project_id,
            ],
            "Supplier refund FX {$refund->reference}",
        );

        return $payload;
    }

    private function accountId(Business $business, string $code): int
    {
        $account = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->activePostable()
            ->first();

        if (! $account) {
            throw new DomainException("Required ledger account [{$code}] is unavailable.");
        }

        return $account->id;
    }

    private function requiredGbpAmount(mixed $amount, string $label): string
    {
        if ($amount === null || (float) $amount <= 0) {
            throw new DomainException("A positive GBP snapshot is required for the {$label}.");
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function exchangeRate(mixed $currency, mixed $rate): string
    {
        if (strtoupper((string) $currency) === 'GBP') {
            return '1.00000000';
        }

        if ($rate === null || (float) $rate <= 0) {
            throw new DomainException('A positive GBP exchange-rate snapshot is required for a foreign-currency payment.');
        }

        return number_format((float) $rate, 8, '.', '');
    }

    private function realisedFx(mixed $amount): float
    {
        $normalized = round((float) ($amount ?? 0), 2);

        return abs($normalized) < 0.005 ? 0.0 : $normalized;
    }

    /**
     * A positive realised value is a gain; a negative value is a loss.
     *
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $dimensions
     */
    private function appendFxLine(
        array &$lines,
        Business $business,
        float $realisedFxGbp,
        array $dimensions,
        string $memo,
        bool $reverse = false,
    ): void {
        if ($realisedFxGbp === 0.0) {
            return;
        }

        $gain = $realisedFxGbp > 0;
        $amount = number_format(abs($realisedFxGbp), 2, '.', '');
        $debit = $reverse ? $gain : ! $gain;

        $lines[] = [
            ...$dimensions,
            'ledger_account_id' => $this->accountId($business, $gain ? '4300' : '7100'),
            'debit_gbp' => $debit ? $amount : '0.00',
            'credit_gbp' => $debit ? '0.00' : $amount,
            'original_currency' => 'GBP',
            'original_amount' => $amount,
            'exchange_rate' => '1.00000000',
            'memo' => $reverse ? "Reversal — {$memo}" : $memo,
        ];
    }

    /** @return array<string, string> */
    private function fxSnapshot(mixed $carryingAmountGbp, float $realisedFxGbp): array
    {
        if ($realisedFxGbp === 0.0) {
            return [];
        }

        return [
            'carrying_amount_gbp' => number_format((float) $carryingAmountGbp, 2, '.', ''),
            'realised_fx_gbp' => number_format($realisedFxGbp, 2, '.', ''),
        ];
    }

    /** @return array<string, mixed> */
    private function paymentSnapshot(Payment $payment, string $event): array
    {
        return [
            'id' => $payment->id,
            'event' => $event,
            'invoice_id' => $payment->invoice_id,
            'reference' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'amount_gbp' => $payment->amount_gbp,
            'gbp_exchange_rate' => $payment->gbp_exchange_rate,
            'paid_at' => $payment->paid_at?->toISOString(),
            'refunded_at' => $payment->refunded_at?->toISOString(),
            ...$this->fxSnapshot($payment->carrying_amount_gbp, $this->realisedFx($payment->realised_fx_gbp)),
        ];
    }
}
