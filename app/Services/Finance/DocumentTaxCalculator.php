<?php

namespace App\Services\Finance;

use App\Models\InvoiceItem;
use App\Models\QuoteItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class DocumentTaxCalculator
{
    /**
     * @param  Collection<int, InvoiceItem|QuoteItem>  $items
     * @return array{subtotal: float, discount_amount: float, vat_amount: float, total: float, subtotal_gbp: float|null, vat_amount_gbp: float|null, total_gbp: float|null}
     */
    public function calculate(
        Collection $items,
        float $discountAmount,
        float $fallbackVatRate,
        ?CarbonInterface $supplyDate = null,
        ?string $currency = 'GBP',
        ?float $gbpExchangeRate = null,
    ): array {
        $gbpRate = strtoupper((string) $currency) === 'GBP'
            ? 1.0
            : (($gbpExchangeRate ?? 0) > 0 ? $gbpExchangeRate : null);
        $lineAmounts = $items
            ->map(fn (InvoiceItem|QuoteItem $item): int => $this->toMinorUnits(
                (float) $item->quantity * (float) $item->unit_price
            ))
            ->values()
            ->all();

        $subtotal = array_sum($lineAmounts);
        $discount = min(max($this->toMinorUnits($discountAmount), 0), $subtotal);
        $discountAllocations = $this->allocateProportionally($discount, $lineAmounts);
        $vatTotal = 0;
        $grossTotal = 0;
        $vatTotalGbp = 0;
        $grossTotalGbp = 0;

        foreach ($items->values() as $index => $item) {
            $amount = $lineAmounts[$index];
            $netAmount = $amount - $discountAllocations[$index];
            $vatRate = $item->tax_code_id
                ? (float) ($item->vat_rate_snapshot ?? 0)
                : $fallbackVatRate;
            $vatRateBasisPoints = (int) round($vatRate * 100);
            $vatAmount = (int) round(($netAmount * $vatRateBasisPoints) / 10000);
            $grossAmount = $netAmount + $vatAmount;
            $netAmountGbp = $this->convertMinorUnitsToGbp($netAmount, $gbpRate);
            $vatAmountGbp = $this->convertMinorUnitsToGbp($vatAmount, $gbpRate);
            $grossAmountGbp = $this->convertMinorUnitsToGbp($grossAmount, $gbpRate);

            $item->updateQuietly([
                'amount' => $this->fromMinorUnits($amount),
                'net_amount' => $this->fromMinorUnits($netAmount),
                'net_amount_gbp' => $this->fromNullableMinorUnits($netAmountGbp),
                'vat_rate_snapshot' => $vatRate,
                'vat_amount' => $this->fromMinorUnits($vatAmount),
                'vat_amount_gbp' => $this->fromNullableMinorUnits($vatAmountGbp),
                'gross_amount' => $this->fromMinorUnits($grossAmount),
                'gross_amount_gbp' => $this->fromNullableMinorUnits($grossAmountGbp),
                'supply_date' => $item->supply_date ?? $supplyDate,
            ]);

            $vatTotal += $vatAmount;
            $grossTotal += $grossAmount;
            $vatTotalGbp += $vatAmountGbp ?? 0;
            $grossTotalGbp += $grossAmountGbp ?? 0;
        }

        return [
            'subtotal' => $this->fromMinorUnits($subtotal),
            'discount_amount' => $this->fromMinorUnits($discount),
            'vat_amount' => $this->fromMinorUnits($vatTotal),
            'total' => $this->fromMinorUnits($grossTotal),
            'subtotal_gbp' => $this->fromNullableMinorUnits($this->convertMinorUnitsToGbp($subtotal, $gbpRate)),
            'vat_amount_gbp' => $gbpRate !== null ? $this->fromMinorUnits($vatTotalGbp) : null,
            'total_gbp' => $gbpRate !== null ? $this->fromMinorUnits($grossTotalGbp) : null,
        ];
    }

    /**
     * @param  list<int>  $amounts
     * @return list<int>
     */
    private function allocateProportionally(int $value, array $amounts): array
    {
        $total = array_sum($amounts);

        if ($value === 0 || $total === 0) {
            return array_fill(0, count($amounts), 0);
        }

        $allocations = [];
        $remainders = [];

        foreach ($amounts as $index => $amount) {
            $rawAllocation = $value * ($amount / $total);
            $allocations[$index] = (int) floor($rawAllocation);
            $remainders[$index] = $rawAllocation - $allocations[$index];
        }

        $penniesLeft = $value - array_sum($allocations);
        arsort($remainders);

        foreach (array_keys($remainders) as $index) {
            if ($penniesLeft === 0) {
                break;
            }

            $allocations[$index]++;
            $penniesLeft--;
        }

        ksort($allocations);

        return array_values($allocations);
    }

    private function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function fromMinorUnits(int $amount): float
    {
        return $amount / 100;
    }

    private function convertMinorUnitsToGbp(int $amount, ?float $gbpRate): ?int
    {
        return $gbpRate !== null ? (int) round($amount * $gbpRate) : null;
    }

    private function fromNullableMinorUnits(?int $amount): ?float
    {
        return $amount !== null ? $this->fromMinorUnits($amount) : null;
    }
}
