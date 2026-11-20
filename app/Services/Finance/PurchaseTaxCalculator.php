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
        $gbpRate = $this->resolveGbpRate($currency, $gbpExchangeRate);
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
            $vatRate = (float) ($item->vat_rate_snapshot ?? $taxCode?->rate ?? 0);
            $line = $this->calculateLine(
                (float) $item->quantity,
                (float) $item->unit_price,
                $vatRate,
                $taxCode?->type === 'reverse_charge',
                $gbpRate,
            );

            $item->updateQuietly([
                'net_amount' => $this->fromMinorUnits($line['net']),
                'net_amount_gbp' => $this->fromNullableMinorUnits($line['net_gbp']),
                'vat_rate_snapshot' => $vatRate,
                'vat_amount' => $this->fromMinorUnits($line['vat']),
                'vat_amount_gbp' => $this->fromNullableMinorUnits($line['vat_gbp']),
                'gross_amount' => $this->fromMinorUnits($line['gross']),
                'gross_amount_gbp' => $this->fromNullableMinorUnits($line['gross_gbp']),
                'supply_date' => $item->supply_date ?? $supplyDate,
            ]);

            $subtotal += $line['net'];
            $vatTotal += $line['vat'];
            $grossTotal += $line['gross'];
            $subtotalGbp += $line['net_gbp'] ?? 0;
            $vatTotalGbp += $line['vat_gbp'] ?? 0;
            $grossTotalGbp += $line['gross_gbp'] ?? 0;
        }

        return $this->formatTotals(
            $subtotal,
            $vatTotal,
            $grossTotal,
            $subtotalGbp,
            $vatTotalGbp,
            $grossTotalGbp,
            $gbpRate,
        );
    }

    /**
     * Calculate a non-persistent preview using the same line-rounding rules as calculate().
     *
     * @param  list<array<string, mixed>>  $items
     * @return array{subtotal: float, vat_amount: float, total: float, subtotal_gbp: float|null, vat_amount_gbp: float|null, total_gbp: float|null}
     */
    public function preview(
        array $items,
        string $businessId,
        ?string $currency = 'GBP',
        ?float $gbpExchangeRate = null,
    ): array {
        $gbpRate = $this->resolveGbpRate($currency, $gbpExchangeRate);
        $taxCodeIds = collect($items)
            ->pluck('tax_code_id')
            ->filter(fn (mixed $id): bool => filled($id))
            ->unique()
            ->values();
        $taxCodes = TaxCode::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->whereIn('id', $taxCodeIds)
            ->get()
            ->keyBy('id');
        $subtotal = 0;
        $vatTotal = 0;
        $grossTotal = 0;
        $subtotalGbp = 0;
        $vatTotalGbp = 0;
        $grossTotalGbp = 0;

        foreach ($items as $item) {
            $taxCode = $taxCodes->get($item['tax_code_id'] ?? null);
            $line = $this->calculateLine(
                max(0, is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : 0),
                max(0, is_numeric($item['unit_price'] ?? null) ? (float) $item['unit_price'] : 0),
                (float) ($taxCode?->rate ?? 0),
                $taxCode?->type === 'reverse_charge',
                $gbpRate,
            );

            $subtotal += $line['net'];
            $vatTotal += $line['vat'];
            $grossTotal += $line['gross'];
            $subtotalGbp += $line['net_gbp'] ?? 0;
            $vatTotalGbp += $line['vat_gbp'] ?? 0;
            $grossTotalGbp += $line['gross_gbp'] ?? 0;
        }

        return $this->formatTotals(
            $subtotal,
            $vatTotal,
            $grossTotal,
            $subtotalGbp,
            $vatTotalGbp,
            $grossTotalGbp,
            $gbpRate,
        );
    }

    /**
     * @return array{net: int, vat: int, gross: int, net_gbp: int|null, vat_gbp: int|null, gross_gbp: int|null}
     */
    private function calculateLine(
        float $quantity,
        float $unitPrice,
        float $vatRate,
        bool $isReverseCharge,
        ?float $gbpRate,
    ): array {
        $netAmount = $this->toMinorUnits($quantity * $unitPrice);
        $vatAmount = (int) round(($netAmount * (int) round($vatRate * 100)) / 10000);
        $grossAmount = $isReverseCharge ? $netAmount : $netAmount + $vatAmount;

        return [
            'net' => $netAmount,
            'vat' => $vatAmount,
            'gross' => $grossAmount,
            'net_gbp' => $this->convertToGbp($netAmount, $gbpRate),
            'vat_gbp' => $this->convertToGbp($vatAmount, $gbpRate),
            'gross_gbp' => $this->convertToGbp($grossAmount, $gbpRate),
        ];
    }

    /**
     * @return array{subtotal: float, vat_amount: float, total: float, subtotal_gbp: float|null, vat_amount_gbp: float|null, total_gbp: float|null}
     */
    private function formatTotals(
        int $subtotal,
        int $vatTotal,
        int $grossTotal,
        int $subtotalGbp,
        int $vatTotalGbp,
        int $grossTotalGbp,
        ?float $gbpRate,
    ): array {
        return [
            'subtotal' => $this->fromMinorUnits($subtotal),
            'vat_amount' => $this->fromMinorUnits($vatTotal),
            'total' => $this->fromMinorUnits($grossTotal),
            'subtotal_gbp' => $gbpRate !== null ? $this->fromMinorUnits($subtotalGbp) : null,
            'vat_amount_gbp' => $gbpRate !== null ? $this->fromMinorUnits($vatTotalGbp) : null,
            'total_gbp' => $gbpRate !== null ? $this->fromMinorUnits($grossTotalGbp) : null,
        ];
    }

    private function resolveGbpRate(?string $currency, ?float $gbpExchangeRate): ?float
    {
        return strtoupper((string) $currency) === 'GBP'
            ? 1.0
            : (($gbpExchangeRate ?? 0) > 0 ? $gbpExchangeRate : null);
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
