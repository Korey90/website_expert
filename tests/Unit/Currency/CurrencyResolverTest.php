<?php

namespace Tests\Unit\Currency;

use App\Models\Setting;
use App\Services\Currency\CurrencyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyResolverTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(CurrencyResolver::class);
    }

    public function test_resolves_currency_from_locale(): void
    {
        $this->assertSame('PLN', $this->resolver->resolveForLocale('pl'));
        $this->assertSame('GBP', $this->resolver->resolveForLocale('en'));
        $this->assertSame('EUR', $this->resolver->resolveForLocale('pt'));
    }

    public function test_resolves_currency_from_locale_with_region_suffix(): void
    {
        $this->assertSame('PLN', $this->resolver->resolveForLocale('pl_PL'));
        $this->assertSame('GBP', $this->resolver->resolveForLocale('en_GB'));
        $this->assertSame('EUR', $this->resolver->resolveForLocale('pt_PT'));
    }

    public function test_uses_payment_setting_when_locale_is_not_mapped(): void
    {
        Setting::set('payment_currency', 'EUR', 'payments');

        $this->assertSame('EUR', $this->resolver->resolve(locale: 'de'));
    }

    public function test_falls_back_to_default_currency_when_locale_and_setting_are_invalid(): void
    {
        Setting::set('payment_currency', 'AUD', 'payments');

        $this->assertSame('GBP', $this->resolver->resolve(locale: 'de'));
    }

    public function test_normalize_rejects_unsupported_currency(): void
    {
        $this->assertSame('GBP', $this->resolver->normalize('AUD'));
        $this->assertSame('PLN', $this->resolver->normalize('pln'));
    }
}
