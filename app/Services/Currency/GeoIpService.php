<?php

namespace App\Services\Currency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Resolve a 2-letter ISO country code from the request.
     *
     * Detection order:
     *  1. CF-IPCountry header (Cloudflare CDN — most reliable, no extra latency)
     *  2. ip-api.com free API with 24-hour per-IP cache
     *
     * Returns null for private/loopback IPs (local dev) or on persistent API failure.
     * Developer overrides (GEOIP_TEST_COUNTRY) are handled by DetectGeoCurrency middleware.
     */
    public function countryFromRequest(Request $request): ?string
    {
        // 1. Cloudflare header — set automatically when app is behind Cloudflare
        $cfCountry = $request->header('CF-IPCountry');
        if ($cfCountry && $cfCountry !== 'XX' && $this->isValidCountryCode($cfCountry)) {
            return strtoupper($cfCountry);
        }

        $ip = $request->ip();

        // 2. Skip private/loopback addresses (local dev, Docker, tests)
        if ($this->isPrivateIp($ip)) {
            return null;
        }

        return $this->countryFromIp($ip);
    }

    /**
     * Resolve country code for a given IP address, with caching.
     * Uses the 'file' store explicitly to avoid dependency on DB cache table.
     * Only caches successful (non-null) results so failed lookups are retried.
     */
    public function countryFromIp(string $ip): ?string
    {
        $cacheKey = 'geoip_country_'.md5($ip);

        try {
            $store = Cache::store('file');

            if ($store->has($cacheKey)) {
                return $store->get($cacheKey);
            }

            $country = $this->fetchCountryFromApi($ip);

            if ($country !== null) {
                $store->put($cacheKey, $country, self::CACHE_TTL);
            }

            return $country;
        } catch (\Throwable $e) {
            Log::debug('GeoIP cache error, falling back to direct lookup', ['error' => $e->getMessage()]);

            return $this->fetchCountryFromApi($ip);
        }
    }

    private function fetchCountryFromApi(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)
                ->withUserAgent('WebsiteExpert/1.0 (GeoIP lookup)')
                ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,countryCode']);

            if ($response->successful()) {
                $data = $response->json();

                if (
                    ($data['status'] ?? '') === 'success'
                    && isset($data['countryCode'])
                    && $this->isValidCountryCode($data['countryCode'])
                ) {
                    return strtoupper($data['countryCode']);
                }
            }
        } catch (\Throwable $e) {
            Log::debug('GeoIP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function isPrivateIp(string $ip): bool
    {
        if ($ip === '::1') {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function isValidCountryCode(string $code): bool
    {
        return (bool) preg_match('/^[A-Za-z]{2}$/', $code);
    }
}
