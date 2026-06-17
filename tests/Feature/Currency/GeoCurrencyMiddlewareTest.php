<?php

namespace Tests\Feature\Currency;

use App\Http\Middleware\DetectGeoCurrency;
use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\GeoIpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GeoCurrencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure any GEOIP_TEST_COUNTRY set in .env does not bleed into these tests.
        config(['currencies.test_country' => null]);
    }

    // -----------------------------------------------------------------------
    // CurrencyResolver::resolveForCountry
    // -----------------------------------------------------------------------

    public function test_resolver_resolves_gbp_for_uk(): void
    {
        $resolver = app(CurrencyResolver::class);

        $this->assertSame('GBP', $resolver->resolveForCountry('GB'));
    }

    public function test_resolver_resolves_pln_for_poland(): void
    {
        $resolver = app(CurrencyResolver::class);

        $this->assertSame('PLN', $resolver->resolveForCountry('PL'));
    }

    public function test_resolver_resolves_eur_for_european_countries(): void
    {
        $resolver = app(CurrencyResolver::class);

        foreach (['DE', 'FR', 'IT', 'ES', 'PT', 'NL', 'SE', 'NO', 'CH', 'UA'] as $country) {
            $this->assertSame('EUR', $resolver->resolveForCountry($country), "Expected EUR for country {$country}");
        }
    }

    public function test_resolver_falls_back_to_default_for_non_european_countries(): void
    {
        $resolver = app(CurrencyResolver::class);

        $this->assertSame('GBP', $resolver->resolveForCountry('US'));
        $this->assertSame('GBP', $resolver->resolveForCountry('NG'));
        $this->assertSame('GBP', $resolver->resolveForCountry('JP'));
        $this->assertSame('GBP', $resolver->resolveForCountry('ZZ'));
    }

    // -----------------------------------------------------------------------
    // CurrencyResolver::resolve — request attribute priority
    // -----------------------------------------------------------------------

    public function test_resolve_prefers_request_attribute_over_locale(): void
    {
        $resolver = app(CurrencyResolver::class);

        $request = \Illuminate\Http\Request::create('/');
        $request->attributes->set(DetectGeoCurrency::COOKIE_NAME, 'PLN');

        // Even though locale says en → GBP, request attribute wins
        $this->assertSame('PLN', $resolver->resolve($request, 'en'));
    }

    public function test_resolve_falls_back_to_locale_when_no_request_attribute(): void
    {
        $resolver = app(CurrencyResolver::class);

        $request = \Illuminate\Http\Request::create('/');

        $this->assertSame('PLN', $resolver->resolve($request, 'pl'));
    }

    public function test_resolve_ignores_unsupported_value_in_request_attribute(): void
    {
        $resolver = app(CurrencyResolver::class);

        $request = \Illuminate\Http\Request::create('/');
        $request->attributes->set(DetectGeoCurrency::COOKIE_NAME, 'AUD');

        // AUD is not supported, falls through to locale
        $this->assertSame('GBP', $resolver->resolve($request, 'en'));
    }

    // -----------------------------------------------------------------------
    // DetectGeoCurrency middleware — direct invocation (bypasses HTTP stack
    // to avoid interference from unrelated middleware like EncryptCookies).
    // We verify: (a) the request attribute is set correctly, and
    //            (b) GeoIpService interaction matches expectations.
    // -----------------------------------------------------------------------

    public function test_middleware_sets_pln_attribute_for_polish_ip(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->once()->andReturn('PL');

        $request = \Illuminate\Http\Request::create('/');
        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertSame('PLN', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_middleware_sets_gbp_attribute_for_uk_ip(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->once()->andReturn('GB');

        $request = \Illuminate\Http\Request::create('/');
        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertSame('GBP', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_middleware_sets_eur_attribute_for_german_ip(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->once()->andReturn('DE');

        $request = \Illuminate\Http\Request::create('/');
        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertSame('EUR', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_middleware_sets_gbp_attribute_for_non_european_ip(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->once()->andReturn('NG');

        $request = \Illuminate\Http\Request::create('/');
        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertSame('GBP', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_middleware_skips_geo_detection_when_valid_cookie_exists(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->never();

        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(DetectGeoCurrency::COOKIE_NAME, 'PLN'); // existing cookie

        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        // Cookie was valid — attribute reads from cookie, detection skipped
        $this->assertSame('PLN', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_middleware_sets_no_attribute_when_geoip_returns_null(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->once()->andReturn(null);

        $request = \Illuminate\Http\Request::create('/');
        $middleware = new DetectGeoCurrency($geoIpMock, app(\App\Services\Currency\CurrencyResolver::class));
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertNull($request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    // -----------------------------------------------------------------------
    // test_country config override (GEOIP_TEST_COUNTRY)
    // -----------------------------------------------------------------------

    public function test_config_test_country_overrides_cookie_and_detection(): void
    {
        config(['currencies.test_country' => 'PL']);

        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldReceive('countryFromRequest')->never();
        $this->app->instance(GeoIpService::class, $geoIpMock);

        // Even with a GBP cookie already set, test_country=PL must win
        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(DetectGeoCurrency::COOKIE_NAME, 'GBP');

        $middleware = app(DetectGeoCurrency::class);
        $middleware->handle($request, fn ($r) => response('ok'));

        $this->assertSame('PLN', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));

        config(['currencies.test_country' => null]);
    }

    // -----------------------------------------------------------------------
    // GBP geo currency survives language switch
    // -----------------------------------------------------------------------

    public function test_gbp_geo_currency_survives_language_switch_to_polish(): void
    {
        $geoIpMock = Mockery::mock(GeoIpService::class);
        $geoIpMock->shouldNotReceive('countryFromRequest');
        $this->app->instance(GeoIpService::class, $geoIpMock);

        // Visitor has GBP cookie (from UK)
        $request = \Illuminate\Http\Request::create('/');
        $request->cookies->set(DetectGeoCurrency::COOKIE_NAME, 'GBP');

        $middleware = app(DetectGeoCurrency::class);
        $middleware->handle($request, fn ($r) => response('ok'));

        // Request attribute must be GBP, not locale-driven PLN
        $this->assertSame('GBP', $request->attributes->get(DetectGeoCurrency::COOKIE_NAME));
    }

    public function test_resolver_returns_gbp_for_uk_visitor_with_polish_locale(): void
    {
        $resolver = app(CurrencyResolver::class);

        $request = \Illuminate\Http\Request::create('/');
        $request->attributes->set(DetectGeoCurrency::COOKIE_NAME, 'GBP');

        // Polish locale would normally give PLN — geo must override it
        $this->assertSame('GBP', $resolver->resolve($request, 'pl'));
    }

    // -----------------------------------------------------------------------
    // GeoIpService — basic detection
    // -----------------------------------------------------------------------

    public function test_geoip_service_returns_null_for_private_ip(): void
    {
        $service = app(GeoIpService::class);

        $request = \Illuminate\Http\Request::create('/');
        // 127.0.0.1 is private/loopback
        $this->assertNull($service->countryFromRequest($request));
    }

    public function test_geoip_service_reads_cf_ipcountry_header(): void
    {
        $service = app(GeoIpService::class);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_IPCOUNTRY' => 'PL',
            'REMOTE_ADDR' => '185.76.100.1',
        ]);

        $this->assertSame('PL', $service->countryFromRequest($request));
    }

    public function test_geoip_service_ignores_cloudflare_xx_placeholder(): void
    {
        $service = app(GeoIpService::class);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_IPCOUNTRY' => 'XX',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        // XX = unknown, falls through to IP → private IP → null
        $this->assertNull($service->countryFromRequest($request));
    }
}
