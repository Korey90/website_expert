<?php

namespace App\Http\Middleware;

use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\GeoIpService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DetectGeoCurrency
{
    /** Cookie name — stored as plain text (not sensitive: only 'GBP', 'PLN', 'EUR'). */
    public const COOKIE_NAME = 'geo_currency';

    /** 30 days. Currency region rarely changes for a returning visitor. */
    private const COOKIE_TTL_MINUTES = 60 * 24 * 30;

    public function __construct(
        private GeoIpService $geoIpService,
        private CurrencyResolver $currencyResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $currency = $this->detectCurrency($request);

        if ($currency !== null) {
            // Make the resolved currency available to CurrencyResolver within this request
            // without any dependency on session driver or database.
            $request->attributes->set(self::COOKIE_NAME, $currency);
        }

        return $next($request);
    }

    private function detectCurrency(Request $request): ?string
    {
        // 1. Developer / staging override via config('currencies.test_country').
        //    Always wins — ignores any existing cookie.
        $testCountry = config('currencies.test_country');
        if ($testCountry && preg_match('/^[A-Za-z]{2}$/', $testCountry)) {
            return $this->currencyResolver->resolveForCountry(strtoupper($testCountry));
        }

        // 2. Existing geo-currency cookie from a previous visit.
        //    Cookie is NOT encrypted (plain text currency code — not sensitive).
        $existing = $request->cookie(self::COOKIE_NAME);
        if ($existing && $this->currencyResolver->isSupported($existing)) {
            return strtoupper($existing);
        }

        // 3. First visit (or cookie expired): detect country from IP / CF header.
        $country = $this->geoIpService->countryFromRequest($request);
        if ($country !== null) {
            $currency = $this->currencyResolver->resolveForCountry($country);
            // Queue cookie so the browser sends it on every subsequent request.
            Cookie::queue(self::COOKIE_NAME, $currency, self::COOKIE_TTL_MINUTES, '/', null, false, false);

            return $currency;
        }

        return null;
    }
}
