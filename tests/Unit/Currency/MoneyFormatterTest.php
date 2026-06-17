<?php

namespace Tests\Unit\Currency;

use App\Services\Currency\MoneyFormatter;
use Tests\TestCase;

class MoneyFormatterTest extends TestCase
{
    public function test_returns_currency_options_from_config(): void
    {
        $options = app(MoneyFormatter::class)->currencyOptions();

        $this->assertSame('£ GBP', $options['GBP']);
        $this->assertSame('€ EUR', $options['EUR']);
        $this->assertSame('zł PLN', $options['PLN']);
    }
}
