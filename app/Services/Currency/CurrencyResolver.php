<?php

namespace App\Services\Currency;

use App\Http\Middleware\DetectGeoCurrency;
use App\Models\Setting;
use Illuminate\Http\Request;

class CurrencyResolver
{
    public function resolve(?Request $request = null, ?string $locale = null): string
    {
        // 1. GeoIP-resolved currency from request attribute.
        //    Set by DetectGeoCurrency middleware using a long-lived cookie.
        //    This is immune to language switches — currency follows location, not UI language.
        $geo = $request?->attributes->get(DetectGeoCurrency::COOKIE_NAME);
        if ($this->isSupported($geo)) {
            return strtoupper((string) $geo);
        }

        // 2. Locale-based fallback (artisan, API, tests without middleware).
        $localeCurrency = $this->resolveForLocale($locale ?? app()->getLocale());

        if ($localeCurrency !== null) {
            return $localeCurrency;
        }

        try {
            $settingCurrency = Setting::get('payment_currency');
            if ($this->isSupported($settingCurrency)) {
                return strtoupper((string) $settingCurrency);
            }
        } catch (\Throwable) {
            // Settings table may not be available during early boot or tests.
        }

        return $this->defaultCurrency();
    }

    /**
     * Resolve currency for a given ISO 3166-1 alpha-2 country code.
     * Falls back to the application default when the country is not in the map.
     */
    public function resolveForCountry(string $country): string
    {
        $currency = config('currencies.country_map.'.strtoupper($country));

        return $this->isSupported($currency) ? strtoupper((string) $currency) : $this->defaultCurrency();
    }

    public function resolveForLocale(?string $locale): ?string
    {
        $normalized = strtolower(substr((string) $locale, 0, 2));
        $currency = config("currencies.locale_map.{$normalized}");

        return $this->isSupported($currency) ? strtoupper((string) $currency) : null;
    }

    public function defaultCurrency(): string
    {
        $currency = strtoupper((string) config('currencies.default', 'GBP'));

        return $this->isSupported($currency) ? $currency : 'GBP';
    }

    public function isSupported(mixed $currency): bool
    {
        if (! is_string($currency) || $currency === '') {
            return false;
        }

        return array_key_exists(strtoupper($currency), $this->supportedCurrencies());
    }

    public function normalize(mixed $currency): string
    {
        $currency = is_string($currency) ? strtoupper($currency) : null;

        return $this->isSupported($currency) ? $currency : $this->defaultCurrency();
    }

    public function countryForLocale(?string $locale): string
    {
        $normalized = strtolower(substr((string) $locale, 0, 2));
        $country = config("currencies.locale_country_map.{$normalized}");

        return is_string($country) && $country !== '' ? strtoupper($country) : 'GB';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function supportedCurrencies(): array
    {
        return config('currencies.supported', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(?string $currency = null): array
    {
        $currency = $this->normalize($currency);

        return $this->supportedCurrencies()[$currency] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return collect($this->supportedCurrencies())
            ->mapWithKeys(fn (array $meta, string $code) => [
                $code => trim(($meta['symbol'] ?? $code).' '.$code),
            ])
            ->all();
    }
}
