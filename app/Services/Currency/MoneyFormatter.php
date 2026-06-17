<?php

namespace App\Services\Currency;

class MoneyFormatter
{
    public function __construct(
        private readonly CurrencyResolver $currencyResolver,
    ) {}

    public function format(float|int|string|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $this->currencyResolver->normalize($currency);
        $meta = $this->currencyResolver->metadata($currency);
        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $displayLocale = $locale ?: (string) ($meta['display_locale'] ?? app()->getLocale() ?: 'en-GB');

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($displayLocale, \NumberFormatter::CURRENCY);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, (int) ($meta['decimal_digits'] ?? 2));

            $formatted = $formatter->formatCurrency($value, $currency);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        return $this->fallbackFormat($value, $currency, $meta);
    }

    /**
     * @return array<string, string>
     */
    public function currencyOptions(): array
    {
        return $this->currencyResolver->options();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function fallbackFormat(float $amount, string $currency, array $meta): string
    {
        $digits = (int) ($meta['decimal_digits'] ?? 2);
        $number = number_format(
            $amount,
            $digits,
            (string) ($meta['decimal_separator'] ?? '.'),
            (string) ($meta['thousands_separator'] ?? ','),
        );
        $symbol = (string) ($meta['symbol'] ?? $currency);

        return ($meta['symbol_position'] ?? 'before') === 'after'
            ? "{$number} {$symbol}"
            : "{$symbol}{$number}";
    }
}
