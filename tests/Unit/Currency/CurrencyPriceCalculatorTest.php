<?php

namespace Tests\Unit\Currency;

use App\Services\Currency\CurrencyPriceCalculator;
use Tests\TestCase;

class CurrencyPriceCalculatorTest extends TestCase
{
    private CurrencyPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new CurrencyPriceCalculator;
    }

    public function test_rounds_exchange_rate_up_to_nearest_tenth(): void
    {
        $this->assertSame(5.0, $this->calculator->roundRateUp(4.93));
        $this->assertSame(5.2, $this->calculator->roundRateUp(5.11));
        $this->assertSame(1.2, $this->calculator->roundRateUp(1.16));
    }

    public function test_converts_from_base_with_rounded_rate(): void
    {
        $this->assertSame(100.0, $this->calculator->convertFromBase(20, 4.93));
        $this->assertSame(104.0, $this->calculator->convertFromBase(20, 5.11));
    }
}
