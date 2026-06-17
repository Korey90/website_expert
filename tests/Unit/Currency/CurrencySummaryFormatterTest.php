<?php

namespace Tests\Unit\Currency;

use App\Services\Currency\CurrencySummaryFormatter;
use Tests\TestCase;

class CurrencySummaryFormatterTest extends TestCase
{
    public function test_sums_records_by_currency(): void
    {
        $totals = app(CurrencySummaryFormatter::class)->sumByCurrency([
            ['currency' => 'GBP', 'total' => 1200],
            ['currency' => 'PLN', 'total' => 6000],
            ['currency' => 'GBP', 'total' => 300],
        ]);

        $this->assertSame(1500.0, $totals['GBP']);
        $this->assertSame(6000.0, $totals['PLN']);
    }

    public function test_formats_grouped_totals_with_currency_codes(): void
    {
        $summary = app(CurrencySummaryFormatter::class)->formatGrouped([
            'GBP' => 1200,
            'PLN' => 6000,
        ]);

        $this->assertStringContainsString('GBP', $summary);
        $this->assertStringContainsString('PLN', $summary);
    }
}
