<?php

namespace App\Services\Finance;

use App\Models\ExpenseItem;
use App\Models\TaxCode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class PurchaseTaxCalculator
{
    /**
     * @param  Collection<int, ExpenseItem>  $items
     * @return array{subtotal: float, vat_amount: float, total: float, subtotal_gbp: float|null, vat_amount_gbp: float|null, total_gbp: float|null}
     */
    public function calculate(
        Collection $items,
        ?CarbonInterface $supplyDate = null,
        ?string $currency = 'GBP',
        ?float $gbpExchangeRate = null,
    ): array {
        $gbpRate = strtoupper((string) $currency) === 'GBP'
            ? 1.0
            : (($gbpExchangeRate ?? 0) > 0 ? $gbpExchangeRate : null);
        $subtotal = 0;
        $vatTotal = 0;
        $grossTotal = 0;
        $subtotalGbp = 0;
        $vatTotalGbp = 0;
        $grossTotalGbp = 0;

        foreach ($items as $item) {
            $taxCode = $item->tax_code_id
                ? TaxCode::withoutGlobalScopes()
                    ->whereKey($item->tax_code_id)
                    ->where('business_id', $item->business_id)
                    ->first()
                : null;
            $netAmount = $this->toMinorUnits((float) $item->quantity * (float) $item->unit_price);
            $vatRate = (float) ($item->vat_rate_snapshot ?? $taxCode?->rate ?? 0);
            $vatAmount = (int) round(($netAmount * (int) round($vatRate * 100)) / 10000);
            $grossAmount = $taxCode?->type === 'reverse_charge'
                ? $netAmount
                : $netAmount + $vatAmount;
            $netAmountGbp = $this->convertToGbp($netAmount, $gbpRate);
            $vatAmountGbp = $this->convertToGbp($vatAmount, $gbpRate);
            $grossAmountGbp = $this->convertToGbp($grossAmount, $gbpRate);

            $item->updateQuietly([
                'net_amount' => $this->fromMinorUnits($netAmount),
                'net_amount_gbp' => $this->fromNullableMinorUnits($netAmountGbp),
                'vat_rate_snapshot' => $vatRate,
                'vat_amount' => $this->fromMinorUnits($vatAmount),
                'vat_amount_gbp' => $this->fromNullableMinorUnits($vatAmountGbp),
                'gross_amount' => $this->fromMinorUnits($grossAmount),
                'gross_amount_gbp' => $this->fromNullableMinorUnits($grossAmountGbp),
                'supply_date' => $item->supply_date ?? $supplyDate,
            ]);

            $subtotal += $netAmount;
            $vatTotal += $vatAmount;
            $grossTotal += $grossAmount;
            $subtotalGbp += $netAmountGbp ?? 0;
            $vatTotalGbp += $vatAmountGbp ?? 0;
            $grossTotalGbp += $grossAmountGbp ?? 0;
        }

        return [
            'subtotal' => $this->fromMinorUnits($subtotal),
            'vat_amount' => $this->fromMinorUnits($vatTotal),
            'total' => $this->fromMinorUnits($grossTotal),
            'subtotal_gbp' => $gbpRate !== null ? $this->fromMinorUnits($subtotalGbp) : null,
            'vat_amount_gbp' => $gbpRate !== null ? $this->fromMinorUnits($vatTotalGbp) : null,
            'total_gbp' => $gbpRate !== null ? $this->fromMinorUnits($grossTotalGbp) : null,
        ];
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function fromMinorUnits(int $amount): float
    {
        return $amount / 100;
    }

    private function convertToGbp(int $amount, ?float $gbpRate): ?int
    {
        return $gbpRate !== null ? (int) round($amount * $gbpRate) : null;
    }

    private function fromNullableMinorUnits(?int $amount): ?float
    {
        return $amount !== null ? $this->fromMinorUnits($amount) : null;
    }
}
