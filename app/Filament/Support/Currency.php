<?php

namespace App\Filament\Support;

use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\MoneyFormatter;

class Currency
{
    public static function default(): string
    {
        return app(CurrencyResolver::class)->resolve(request());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return app(CurrencyResolver::class)->options();
    }

    public static function symbol(?string $currency = null): string
    {
        return (string) (app(CurrencyResolver::class)->metadata($currency ?? self::default())['symbol'] ?? $currency ?? self::default());
    }

    public static function format(float|int|string|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        return app(MoneyFormatter::class)->format($amount, $currency ?? self::default(), $locale);
    }

    public static function tableCurrency(?object $record = null): string
    {
        $currency = app(CurrencyResolver::class)->normalize($record?->currency ?? self::default());

        return strtolower($currency);
    }
}
