<?php

namespace App\Services\Currency;

use Illuminate\Support\Collection;

class CurrencySummaryFormatter
{
    public function __construct(
        private readonly MoneyFormatter $moneyFormatter,
        private readonly CurrencyResolver $currencyResolver,
    ) {}

    public function format(float|int|string|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        return $this->moneyFormatter->format($amount, $currency, $locale);
    }

    public function formatWithCode(float|int|string|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $this->currencyResolver->normalize($currency);

        return $currency.' '.$this->format($amount, $currency, $locale);
    }

    public function formatMinorWithCode(int $amountMinor, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $this->currencyResolver->normalize($currency);
        $minorUnit = (int) ($this->currencyResolver->metadata($currency)['minor_unit'] ?? 100);

        return $this->formatWithCode($amountMinor / max(1, $minorUnit), $currency, $locale);
    }

    /**
     * @param  iterable<mixed>  $amountsByCurrency
     */
    public function formatGrouped(iterable $amountsByCurrency, string $separator = ' / ', ?string $fallbackCurrency = null): string
    {
        $totals = $this->normalizeGroupedTotals($amountsByCurrency);

        if ($totals->isEmpty()) {
            return $this->formatWithCode(0, $fallbackCurrency ?? $this->currencyResolver->defaultCurrency());
        }

        return $totals
            ->map(fn (float $amount, string $currency) => $this->formatWithCode($amount, $currency))
            ->implode($separator);
    }

    /**
     * @param  iterable<object|array<string, mixed>>  $records
     * @return Collection<string, float>
     */
    public function sumByCurrency(iterable $records, string $amountField = 'total', string $currencyField = 'currency'): Collection
    {
        return collect($records)
            ->reduce(function (Collection $totals, object|array $record) use ($amountField, $currencyField): Collection {
                $currency = $this->currencyResolver->normalize(data_get($record, $currencyField));
                $amount = data_get($record, $amountField, 0);

                $totals[$currency] = (float) ($totals[$currency] ?? 0) + (is_numeric($amount) ? (float) $amount : 0.0);

                return $totals;
            }, collect())
            ->filter(fn (float $amount): bool => abs($amount) > 0.00001)
            ->sortKeys();
    }

    /**
     * @param  iterable<mixed>  $amountsByCurrency
     * @return Collection<string, float>
     */
    public function normalizeGroupedTotals(iterable $amountsByCurrency): Collection
    {
        return collect($amountsByCurrency)
            ->reduce(function (Collection $totals, mixed $value, mixed $key): Collection {
                if (is_array($value) || is_object($value)) {
                    $currency = data_get($value, 'currency', is_string($key) ? $key : null);
                    $amount = data_get($value, 'amount', data_get($value, 'total', data_get($value, 'value', 0)));
                } else {
                    $currency = is_string($key) ? $key : null;
                    $amount = $value;
                }

                $currency = $this->currencyResolver->normalize($currency);
                $totals[$currency] = (float) ($totals[$currency] ?? 0) + (is_numeric($amount) ? (float) $amount : 0.0);

                return $totals;
            }, collect())
            ->filter(fn (float $amount): bool => abs($amount) > 0.00001)
            ->sortKeys();
    }
}
