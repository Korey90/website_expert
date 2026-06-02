<?php

namespace Tests\Unit\Domain;

use App\Models\DomainPriceList;
use App\Services\Domain\DomainPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testy jednostkowe dla DomainPricingService.
 *
 * Uruchomienie:
 *   php artisan test --filter=DomainPricingServiceTest
 */
class DomainPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private DomainPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DomainPricingService::class);

        DomainPriceList::insert([
            [
                'tld'            => '.com',
                'register_price' => 10.00,
                'renew_price'    => 12.00,
                'transfer_price' => 10.00,
                'currency'       => 'GBP',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'tld'            => '.co.uk',
                'register_price' => 8.00,
                'renew_price'    => 9.00,
                'transfer_price' => null,
                'currency'       => 'GBP',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'tld'            => '.net',
                'register_price' => 9.00,
                'renew_price'    => 11.00,
                'transfer_price' => 9.00,
                'currency'       => 'GBP',
                'is_active'      => false,  // inactive
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }

    // ── getPriceForTld ────────────────────────────────────────────────────────

    public function test_get_price_for_known_active_tld_returns_snapshot(): void
    {
        $snapshot = $this->service->getPriceForTld('.com');

        $this->assertNotNull($snapshot);
        $this->assertSame(10.0, $snapshot->registerPrice);
        $this->assertSame(12.0, $snapshot->renewPrice);
        $this->assertSame('GBP', $snapshot->currency);
    }

    public function test_get_price_for_co_uk_returns_correct_prices(): void
    {
        $snapshot = $this->service->getPriceForTld('.co.uk');

        $this->assertNotNull($snapshot);
        $this->assertSame(8.0, $snapshot->registerPrice);
        $this->assertSame(9.0, $snapshot->renewPrice);
    }

    public function test_get_price_for_inactive_tld_returns_null(): void
    {
        $this->assertNull($this->service->getPriceForTld('.net'));
    }

    public function test_get_price_for_unknown_tld_returns_null(): void
    {
        $this->assertNull($this->service->getPriceForTld('.xyz'));
    }

    // ── calculateRetailPrice ──────────────────────────────────────────────────

    public function test_calculate_retail_price_register_single_year(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 1, 'register');

        $this->assertSame(10.0, $price);
    }

    public function test_calculate_retail_price_register_two_years(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 2, 'register');

        $this->assertSame(20.0, $price);
    }

    public function test_calculate_retail_price_register_three_years(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 3, 'register');

        $this->assertSame(30.0, $price);
    }

    public function test_calculate_retail_price_renew_single_year(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 1, 'renew');

        $this->assertSame(12.0, $price);
    }

    public function test_calculate_retail_price_renew_multi_year(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 2, 'renew');

        $this->assertSame(24.0, $price);
    }

    public function test_calculate_retail_price_transfer(): void
    {
        $price = $this->service->calculateRetailPrice('.com', 1, 'transfer');

        $this->assertSame(10.0, $price);
    }

    public function test_calculate_retail_price_transfer_falls_back_to_register_when_transfer_price_null(): void
    {
        // .co.uk has transfer_price = null → should fall back to register_price
        $price = $this->service->calculateRetailPrice('.co.uk', 1, 'transfer');

        $this->assertSame(8.0, $price);
    }

    public function test_calculate_retail_price_returns_null_for_inactive_tld(): void
    {
        $this->assertNull($this->service->calculateRetailPrice('.net', 1, 'register'));
    }

    public function test_calculate_retail_price_returns_null_for_unknown_tld(): void
    {
        $this->assertNull($this->service->calculateRetailPrice('.xyz', 1, 'register'));
    }

    public function test_calculate_retail_price_co_uk_multi_year(): void
    {
        $price = $this->service->calculateRetailPrice('.co.uk', 3, 'register');

        $this->assertSame(24.0, $price);
    }

    // ── getAllActivePrices ─────────────────────────────────────────────────────
    // getAllActivePrices() uses MySQL-specific FIELD() ordering; these tests
    // are skipped on SQLite (used in CI) and run on MySQL only.

    public function test_get_all_active_prices_excludes_inactive_tlds(): void
    {
        if (\DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('FIELD() not supported on SQLite — run against MySQL.');
        }

        $prices = $this->service->getAllActivePrices();
        $tlds   = $prices->pluck('tld')->toArray();

        $this->assertContains('.com',   $tlds);
        $this->assertContains('.co.uk', $tlds);
        $this->assertNotContains('.net', $tlds);
    }

    public function test_get_all_active_prices_returns_collection_of_snapshots(): void
    {
        if (\DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('FIELD() not supported on SQLite — run against MySQL.');
        }

        $prices = $this->service->getAllActivePrices();

        $this->assertCount(2, $prices);

        foreach ($prices as $snapshot) {
            $this->assertNotNull($snapshot->registerPrice);
            $this->assertNotNull($snapshot->renewPrice);
        }
    }
}
