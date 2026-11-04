<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use DomainException;

class SalesJournalPayloadFactory
{
    /** @return array<string, mixed> */
    public function forInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('items');
        $business = Business::query()->findOrFail($invoice->business_id);
        $currency = strtoupper((string) $invoice->currency);
        $rate = $this->exchangeRate($currency, $invoice->gbp_exchange_rate);
        $receivable = $this->accountId($business, '1200');
        $sales = $this->accountId($business, '4100');
        $vatPayable = $this->accountId($business, '2200');
        $lines = [[
            'ledger_account_id' => $receivable,
            'client_id' => $invoice->client_id,
            'project_id' => $invoice->project_id,
            'debit_gbp' => $this->gbpAmount($currency, $invoice->total_gbp, $invoice->total, 'invoice total'),
            'credit_gbp' => '0.00',
            'original_currency' => $currency,
            'original_amount' => $invoice->total,
            'exchange_rate' => $rate,
            'memo' => "Trade receivable {$invoice->number}",
        ]];

        foreach ($invoice->items as $item) {
            $netGbp = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'invoice item net amount');
            $vatGbp = $this->gbpAmount($currency, $item->vat_amount_gbp, $item->vat_amount, 'invoice item VAT amount');

            if ((float) $netGbp > 0) {
                $lines[] = [
                    'ledger_account_id' => $sales,
                    'tax_code_id' => $item->tax_code_id,
                    'client_id' => $invoice->client_id,
                    'project_id' => $invoice->project_id,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $netGbp,
                    'original_currency' => $currency,
                    'original_amount' => $item->net_amount,
                    'exchange_rate' => $rate,
                    'memo' => $item->description,
                ];
            }

            if ((float) $vatGbp > 0) {
                $lines[] = [
                    'ledger_account_id' => $vatPayable,
                    'tax_code_id' => $item->tax_code_id,
                    'client_id' => $invoice->client_id,
                    'project_id' => $invoice->project_id,
                    'debit_gbp' => '0.00',
                    'credit_gbp' => $vatGbp,
                    'original_currency' => $currency,
                    'original_amount' => $item->vat_amount,
                    'exchange_rate' => $rate,
                    'memo' => "VAT — {$item->description}",
                ];
            }
        }

        return [
            'entry_date' => ($invoice->tax_point ?? $invoice->issue_date)->toDateString(),
            'description' => "Sales invoice {$invoice->number}",
            'idempotency_key' => "sales_invoice:{$invoice->id}:issue",
            'source' => $invoice,
            'source_snapshot' => $this->invoiceSnapshot($invoice),
            'lines' => $lines,
        ];
    }

    /** @return array<string, mixed> */
    public function forCreditNote(CreditNote $creditNote): array
    {
        $creditNote->loadMissing(['items', 'invoice']);
        $business = Business::query()->findOrFail($creditNote->business_id);
        $currency = strtoupper((string) $creditNote->currency);
        $rate = $this->exchangeRate($currency, $creditNote->gbp_exchange_rate);
        $receivable = $this->accountId($business, '1200');
        $sales = $this->accountId($business, '4100');
        $vatPayable = $this->accountId($business, '2200');
        $lines = [];

        foreach ($creditNote->items as $item) {
            $netGbp = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'credit note item net amount');
            $vatGbp = $this->gbpAmount($currency, $item->vat_amount_gbp, $item->vat_amount, 'credit note item VAT amount');

            if ((float) $netGbp > 0) {
                $lines[] = [
                    'ledger_account_id' => $sales,
                    'tax_code_id' => $item->tax_code_id,
                    'client_id' => $creditNote->client_id,
                    'project_id' => $creditNote->invoice?->project_id,
                    'debit_gbp' => $netGbp,
                    'credit_gbp' => '0.00',
                    'original_currency' => $currency,
                    'original_amount' => $item->net_amount,
                    'exchange_rate' => $rate,
                    'memo' => $item->description,
                ];
            }

            if ((float) $vatGbp > 0) {
                $lines[] = [
                    'ledger_account_id' => $vatPayable,
                    'tax_code_id' => $item->tax_code_id,
                    'client_id' => $creditNote->client_id,
                    'project_id' => $creditNote->invoice?->project_id,
                    'debit_gbp' => $vatGbp,
                    'credit_gbp' => '0.00',
                    'original_currency' => $currency,
                    'original_amount' => $item->vat_amount,
                    'exchange_rate' => $rate,
                    'memo' => "VAT credit — {$item->description}",
                ];
            }
        }

        $lines[] = [
            'ledger_account_id' => $receivable,
            'client_id' => $creditNote->client_id,
            'project_id' => $creditNote->invoice?->project_id,
            'debit_gbp' => '0.00',
            'credit_gbp' => $this->gbpAmount($currency, $creditNote->total_gbp, $creditNote->total, 'credit note total'),
            'original_currency' => $currency,
            'original_amount' => $creditNote->total,
            'exchange_rate' => $rate,
            'memo' => "Trade receivable credit {$creditNote->number}",
        ];

        return [
            'entry_date' => ($creditNote->tax_point ?? $creditNote->issue_date)->toDateString(),
            'description' => "Sales credit note {$creditNote->number}",
            'idempotency_key' => "sales_credit_note:{$creditNote->id}:issue",
            'source' => $creditNote,
            'source_snapshot' => $this->creditNoteSnapshot($creditNote),
            'lines' => $lines,
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
            throw new DomainException('A positive GBP exchange-rate snapshot is required for a foreign-currency sale.');
        }

        return number_format((float) $rate, 8, '.', '');
    }

    /** @return array<string, mixed> */
    private function invoiceSnapshot(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'client_id' => $invoice->client_id,
            'project_id' => $invoice->project_id,
            'currency' => $invoice->currency,
            'tax_point' => ($invoice->tax_point ?? $invoice->issue_date)->toDateString(),
            'subtotal' => $invoice->subtotal,
            'vat_amount' => $invoice->vat_amount,
            'total' => $invoice->total,
            'subtotal_gbp' => $invoice->subtotal_gbp,
            'vat_amount_gbp' => $invoice->vat_amount_gbp,
            'total_gbp' => $invoice->total_gbp,
            'items' => $invoice->items->map(fn ($item): array => [
                'id' => $item->id,
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

    /** @return array<string, mixed> */
    private function creditNoteSnapshot(CreditNote $creditNote): array
    {
        return [
            'id' => $creditNote->id,
            'number' => $creditNote->number,
            'invoice_id' => $creditNote->invoice_id,
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
                'invoice_item_id' => $item->invoice_item_id,
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
