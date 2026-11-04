<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\Expense;
use App\Models\LedgerAccount;
use App\Models\PurchaseCreditNote;
use DomainException;

class PurchaseJournalPayloadFactory
{
    /** @return array<string, mixed> */
    public function forExpense(Expense $expense): array
    {
        $expense->loadMissing(['items.taxCode', 'supplier']);
        $business = Business::query()->findOrFail($expense->business_id);
        $currency = strtoupper((string) $expense->currency);
        $rate = $this->exchangeRate($currency, $expense->gbp_exchange_rate);
        $tradeCreditors = $this->accountId($business, '2100');
        $vatReceivable = $this->accountId($business, '1300');
        $vatPayable = $this->accountId($business, '2200');
        $lines = [];

        foreach ($expense->items as $item) {
            $taxCode = $item->taxCode;
            $netGbp = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'expense item net amount');
            $vatGbp = $this->gbpAmount($currency, $item->vat_amount_gbp, $item->vat_amount, 'expense item VAT amount');
            $reverseCharge = $taxCode?->type === 'reverse_charge';
            $recoverableVat = $taxCode?->input_eligible && (! $reverseCharge || $taxCode->output_eligible)
                ? $vatGbp
                : '0.00';
            $outputVat = $reverseCharge && $taxCode?->output_eligible ? $vatGbp : '0.00';
            $nonRecoverableVat = $reverseCharge
                ? $this->subtract($outputVat, $recoverableVat)
                : ($taxCode?->input_eligible ? '0.00' : $vatGbp);
            $expenseDebit = $this->add($netGbp, $nonRecoverableVat);

            if ((float) $expenseDebit > 0) {
                $lines[] = $this->line($expense, $item, [
                    'ledger_account_id' => $item->ledger_account_id,
                    'debit_gbp' => $expenseDebit,
                    'credit_gbp' => '0.00',
                    'original_amount' => $this->add($item->net_amount, $this->originalNonRecoverableVat($item->vat_amount, $reverseCharge, $taxCode?->input_eligible, $taxCode?->output_eligible)),
                    'memo' => $item->description,
                ], $currency, $rate);
            }

            if ((float) $recoverableVat > 0) {
                $lines[] = $this->line($expense, $item, [
                    'ledger_account_id' => $vatReceivable,
                    'debit_gbp' => $recoverableVat,
                    'credit_gbp' => '0.00',
                    'original_amount' => $item->vat_amount,
                    'memo' => "Input VAT — {$item->description}",
                ], $currency, $rate);
            }

            if ((float) $outputVat > 0) {
                $lines[] = $this->line($expense, $item, [
                    'ledger_account_id' => $vatPayable,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $outputVat,
                    'original_amount' => $item->vat_amount,
                    'memo' => "Reverse charge VAT — {$item->description}",
                ], $currency, $rate);
            }
        }

        $lines[] = [
            'ledger_account_id' => $tradeCreditors,
            'supplier_id' => $expense->supplier_id,
            'client_id' => $expense->client_id,
            'project_id' => $expense->project_id,
            'debit_gbp' => '0.00',
            'credit_gbp' => $this->gbpAmount($currency, $expense->total_gbp, $expense->total, 'expense total'),
            'original_currency' => $currency,
            'original_amount' => $expense->total,
            'exchange_rate' => $rate,
            'memo' => 'Trade creditor '.($expense->supplier_invoice_number ?: "expense {$expense->id}"),
        ];

        return [
            'entry_date' => ($expense->tax_point ?? $expense->issue_date)->toDateString(),
            'description' => 'Purchase invoice '.($expense->supplier_invoice_number ?: "#{$expense->id}"),
            'idempotency_key' => "purchase_expense:{$expense->id}:post",
            'source' => $expense,
            'source_snapshot' => $this->expenseSnapshot($expense),
            'lines' => $lines,
        ];
    }

    /** @return array<string, mixed> */
    public function forCreditNote(PurchaseCreditNote $creditNote): array
    {
        $creditNote->loadMissing(['items.taxCode', 'items.expenseItem', 'expense']);
        $business = Business::query()->findOrFail($creditNote->business_id);
        $currency = strtoupper((string) $creditNote->currency);
        $rate = $this->exchangeRate($currency, $creditNote->gbp_exchange_rate);
        $tradeCreditors = $this->accountId($business, '2100');
        $vatReceivable = $this->accountId($business, '1300');
        $vatPayable = $this->accountId($business, '2200');
        $lines = [[
            'ledger_account_id' => $tradeCreditors,
            'supplier_id' => $creditNote->supplier_id,
            'client_id' => $creditNote->expense?->client_id,
            'project_id' => $creditNote->expense?->project_id,
            'debit_gbp' => $this->gbpAmount($currency, $creditNote->total_gbp, $creditNote->total, 'purchase credit note total'),
            'credit_gbp' => '0.00',
            'original_currency' => $currency,
            'original_amount' => $creditNote->total,
            'exchange_rate' => $rate,
            'memo' => "Trade creditor credit {$creditNote->number}",
        ]];

        foreach ($creditNote->items as $item) {
            $taxCode = $item->taxCode;
            $netGbp = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'purchase credit item net amount');
            $vatGbp = $this->gbpAmount($currency, $item->vat_amount_gbp, $item->vat_amount, 'purchase credit item VAT amount');
            $reverseCharge = $taxCode?->type === 'reverse_charge';
            $recoverableVat = $taxCode?->input_eligible && (! $reverseCharge || $taxCode->output_eligible)
                ? $vatGbp
                : '0.00';
            $outputVat = $reverseCharge && $taxCode?->output_eligible ? $vatGbp : '0.00';
            $nonRecoverableVat = $reverseCharge
                ? $this->subtract($outputVat, $recoverableVat)
                : ($taxCode?->input_eligible ? '0.00' : $vatGbp);
            $expenseCredit = $this->add($netGbp, $nonRecoverableVat);
            $expenseItem = $item->expenseItem;

            if ((float) $expenseCredit > 0) {
                $lines[] = $this->creditLine($creditNote, $item, [
                    'ledger_account_id' => $expenseItem?->ledger_account_id,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $expenseCredit,
                    'original_amount' => $this->add($item->net_amount, $this->originalNonRecoverableVat($item->vat_amount, $reverseCharge, $taxCode?->input_eligible, $taxCode?->output_eligible)),
                    'memo' => $item->description,
                ], $currency, $rate);
            }

            if ((float) $recoverableVat > 0) {
                $lines[] = $this->creditLine($creditNote, $item, [
                    'ledger_account_id' => $vatReceivable,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $recoverableVat,
                    'original_amount' => $item->vat_amount,
                    'memo' => "Input VAT credit — {$item->description}",
                ], $currency, $rate);
            }

            if ((float) $outputVat > 0) {
                $lines[] = $this->creditLine($creditNote, $item, [
                    'ledger_account_id' => $vatPayable,
                    'debit_gbp' => $outputVat,
                    'credit_gbp' => '0.00',
                    'original_amount' => $item->vat_amount,
                    'memo' => "Reverse charge VAT credit — {$item->description}",
                ], $currency, $rate);
            }
        }

        return [
            'entry_date' => ($creditNote->tax_point ?? $creditNote->issue_date)->toDateString(),
            'description' => "Purchase credit note {$creditNote->number}",
            'idempotency_key' => "purchase_credit_note:{$creditNote->id}:issue",
            'source' => $creditNote,
            'source_snapshot' => $this->creditNoteSnapshot($creditNote),
            'lines' => $lines,
        ];
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function line(Expense $expense, mixed $item, array $values, string $currency, string $rate): array
    {
        if (blank($values['ledger_account_id'] ?? null)) {
            throw new DomainException('Every expense item requires an active ledger account before posting.');
        }

        return [
            'tax_code_id' => $item->tax_code_id,
            'supplier_id' => $expense->supplier_id,
            'client_id' => $item->client_id ?? $expense->client_id,
            'project_id' => $item->project_id ?? $expense->project_id,
            'original_currency' => $currency,
            'exchange_rate' => $rate,
            ...$values,
        ];
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function creditLine(PurchaseCreditNote $creditNote, mixed $item, array $values, string $currency, string $rate): array
    {
        if (blank($values['ledger_account_id'] ?? null)) {
            throw new DomainException('Every credited expense item requires an active ledger account before posting.');
        }

        return [
            'tax_code_id' => $item->tax_code_id,
            'supplier_id' => $creditNote->supplier_id,
            'client_id' => $item->expenseItem?->client_id ?? $creditNote->expense?->client_id,
            'project_id' => $item->expenseItem?->project_id ?? $creditNote->expense?->project_id,
            'original_currency' => $currency,
            'exchange_rate' => $rate,
            ...$values,
        ];
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

    private function gbpAmount(string $currency, mixed $gbpAmount, mixed $originalAmount, string $label): string
    {
        $amount = $gbpAmount ?? ($currency === 'GBP' ? $originalAmount : null);

        if ($amount === null) {
            throw new DomainException("A GBP snapshot is required for the {$label}.");
        }

        return number_format((float) $amount, 2, '.', '');
    }

    private function exchangeRate(string $currency, mixed $rate): string
    {
        if ($currency === 'GBP') {
            return '1.00000000';
        }

        if ($rate === null || (float) $rate <= 0) {
            throw new DomainException('A positive GBP exchange-rate snapshot is required for a foreign-currency purchase.');
        }

        return number_format((float) $rate, 8, '.', '');
    }

    private function add(mixed $left, mixed $right): string
    {
        return number_format(round((float) $left + (float) $right, 2), 2, '.', '');
    }

    private function subtract(mixed $left, mixed $right): string
    {
        return number_format(max(0, round((float) $left - (float) $right, 2)), 2, '.', '');
    }

    private function originalNonRecoverableVat(mixed $vat, bool $reverseCharge, ?bool $inputEligible, ?bool $outputEligible): string
    {
        if ($reverseCharge) {
            return $outputEligible && ! $inputEligible ? number_format((float) $vat, 2, '.', '') : '0.00';
        }

        return $inputEligible ? '0.00' : number_format((float) $vat, 2, '.', '');
    }

    /** @return array<string, mixed> */
    private function expenseSnapshot(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'supplier_id' => $expense->supplier_id,
            'supplier_invoice_number' => $expense->supplier_invoice_number,
            'currency' => $expense->currency,
            'tax_point' => ($expense->tax_point ?? $expense->issue_date)->toDateString(),
            'subtotal' => $expense->subtotal,
            'vat_amount' => $expense->vat_amount,
            'total' => $expense->total,
            'subtotal_gbp' => $expense->subtotal_gbp,
            'vat_amount_gbp' => $expense->vat_amount_gbp,
            'total_gbp' => $expense->total_gbp,
            'items' => $expense->items->map(fn ($item): array => [
                'id' => $item->id,
                'ledger_account_id' => $item->ledger_account_id,
                'tax_code_id' => $item->tax_code_id,
                'client_id' => $item->client_id,
                'project_id' => $item->project_id,
                'net_amount' => $item->net_amount,
                'vat_amount' => $item->vat_amount,
                'gross_amount' => $item->gross_amount,
                'net_amount_gbp' => $item->net_amount_gbp,
                'vat_amount_gbp' => $item->vat_amount_gbp,
                'gross_amount_gbp' => $item->gross_amount_gbp,
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function creditNoteSnapshot(PurchaseCreditNote $creditNote): array
    {
        return [
            'id' => $creditNote->id,
            'number' => $creditNote->number,
            'expense_id' => $creditNote->expense_id,
            'supplier_id' => $creditNote->supplier_id,
            'currency' => $creditNote->currency,
            'tax_point' => ($creditNote->tax_point ?? $creditNote->issue_date)->toDateString(),
            'reason' => $creditNote->reason,
            'subtotal' => $creditNote->subtotal,
            'vat_amount' => $creditNote->vat_amount,
            'total' => $creditNote->total,
            'subtotal_gbp' => $creditNote->subtotal_gbp,
            'vat_amount_gbp' => $creditNote->vat_amount_gbp,
            'total_gbp' => $creditNote->total_gbp,
            'items' => $creditNote->items->map(fn ($item): array => [
                'id' => $item->id,
                'expense_item_id' => $item->expense_item_id,
                'ledger_account_id' => $item->expenseItem?->ledger_account_id,
                'tax_code_id' => $item->tax_code_id,
                'net_amount' => $item->net_amount,
                'vat_amount' => $item->vat_amount,
                'gross_amount' => $item->gross_amount,
                'net_amount_gbp' => $item->net_amount_gbp,
                'vat_amount_gbp' => $item->vat_amount_gbp,
                'gross_amount_gbp' => $item->gross_amount_gbp,
            ])->all(),
        ];
    }
}
