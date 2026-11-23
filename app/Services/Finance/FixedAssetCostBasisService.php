<?php

namespace App\Services\Finance;

use App\Models\ExpenseItem;
use App\Models\InvoiceItem;
use DomainException;

class FixedAssetCostBasisService
{
    public function forExpenseItem(ExpenseItem $item): string
    {
        $item->loadMissing(['expense', 'taxCode']);
        $currency = strtoupper((string) $item->expense?->currency);
        $net = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'expense item net amount');
        $vat = $this->gbpAmount($currency, $item->vat_amount_gbp, $item->vat_amount, 'expense item VAT amount');
        $taxCode = $item->taxCode;
        $reverseCharge = $taxCode?->type === 'reverse_charge';
        $recoverableVat = $taxCode?->input_eligible && (! $reverseCharge || $taxCode->output_eligible) ? $vat : 0;
        $outputVat = $reverseCharge && $taxCode?->output_eligible ? $vat : 0;
        $nonRecoverableVat = $reverseCharge
            ? max(0, $outputVat - $recoverableVat)
            : ($taxCode?->input_eligible ? 0 : $vat);
        $cost = $net + $nonRecoverableVat;

        if ($cost <= 0) {
            throw new DomainException('The capitalised expense item value must be positive.');
        }

        return $this->fromMinorUnits($cost);
    }

    public function forInvoiceItem(InvoiceItem $item): string
    {
        $item->loadMissing('invoice');
        $currency = strtoupper((string) $item->invoice?->currency);
        $net = $this->gbpAmount($currency, $item->net_amount_gbp, $item->net_amount, 'invoice item net amount');

        if ($net <= 0) {
            throw new DomainException('The disposal proceeds must be positive.');
        }

        return $this->fromMinorUnits($net);
    }

    public function toMinorUnits(mixed $amount, string $label = 'amount'): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            throw new DomainException("The {$label} must be a non-negative GBP amount with at most two decimal places.");
        }

        $whole = (int) $matches[1];
        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ($whole * 100) + (int) $fraction;
    }

    public function fromMinorUnits(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $absolute = abs($amount);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    private function gbpAmount(string $currency, mixed $gbpAmount, mixed $originalAmount, string $label): int
    {
        $amount = $gbpAmount ?? ($currency === 'GBP' ? $originalAmount : null);

        if ($amount === null) {
            throw new DomainException("A GBP snapshot is required for the {$label}.");
        }

        return $this->toMinorUnits($amount, $label);
    }
}
