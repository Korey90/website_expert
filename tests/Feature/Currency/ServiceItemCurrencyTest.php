<?php

namespace Tests\Feature\Currency;

use App\Models\CalculatorPricing;
use App\Models\ServiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceItemCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_services_index_uses_locale_currency_price(): void
    {
        $this->serviceItem([
            'price_from_prices' => [
                'GBP' => 799,
                'EUR' => 958.8,
                'PLN' => 3995,
            ],
            'price_from_period' => 'one_time',
        ]);

        $this->withSession(['locale' => 'pl'])
            ->get('/services')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Services/Index')
                ->where('locale', 'pl')
                ->where('items.0.price_from_currency', 'PLN')
                ->where('items.0.price_from_amount', 3995)
                ->where('items.0.price_from_period', 'one_time'));
    }

    public function test_service_detail_uses_portuguese_locale_currency_price(): void
    {
        $this->serviceItem([
            'slug' => 'seo',
            'price_from_prices' => [
                'GBP' => 499,
                'EUR' => 598.8,
                'PLN' => 2495,
            ],
            'price_from_period' => 'monthly',
        ]);

        $this->withSession(['locale' => 'pt'])
            ->get('/services/seo')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Services/Show')
                ->where('locale', 'pt')
                ->where('item.price_from_currency', 'EUR')
                ->where('item.price_from_amount', 598.8)
                ->where('item.price_from_period', 'monthly'));
    }

    public function test_service_detail_falls_back_to_default_currency_price(): void
    {
        $this->serviceItem([
            'slug' => 'audit',
            'price_from_prices' => [
                'GBP' => 299,
            ],
            'price_from_period' => 'one_time',
        ]);

        $this->withSession(['locale' => 'pl'])
            ->get('/services/audit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Services/Show')
                ->where('item.price_from_currency', 'GBP')
                ->where('item.price_from_amount', 299));
    }

    public function test_calculator_pricing_uses_locale_currency_price(): void
    {
        $this->calculatorPricing([
            'currency' => 'GBP',
            'base_cost' => 400,
        ]);
        $this->calculatorPricing([
            'currency' => 'PLN',
            'base_cost' => 2000,
        ]);

        $this->withSession(['locale' => 'pl'])
            ->get('/calculate')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kalkulator')
                ->where('pricing._currency', 'PLN')
                ->where('pricing.projectType.landing.currency', 'PLN')
                ->where('pricing.projectType.landing.base', 2000));
    }

    public function test_calculator_pricing_falls_back_to_default_currency_price(): void
    {
        $this->calculatorPricing([
            'currency' => 'GBP',
            'base_cost' => 400,
        ]);

        $this->withSession(['locale' => 'pl'])
            ->get('/calculate')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kalkulator')
                ->where('pricing._currency', 'GBP')
                ->where('pricing.projectType.landing.currency', 'GBP')
                ->where('pricing.projectType.landing.base', 400));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function serviceItem(array $overrides = []): ServiceItem
    {
        return ServiceItem::create(array_merge([
            'title' => [
                'en' => 'Service',
                'pl' => 'Usluga',
                'pt' => 'Servico',
            ],
            'description' => [
                'en' => 'Short description.',
                'pl' => 'Krotki opis.',
                'pt' => 'Descricao curta.',
            ],
            'icon' => 'settings',
            'price_from' => '£799',
            'slug' => 'service',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function calculatorPricing(array $overrides = []): CalculatorPricing
    {
        return CalculatorPricing::create(array_merge([
            'category' => 'project_type',
            'key' => 'landing',
            'icon' => 'target',
            'label' => 'Landing Page',
            'label_pl' => 'Landing page',
            'label_pt' => 'Landing Page',
            'description' => 'Single page.',
            'desc_pl' => 'Jedna strona.',
            'desc_pt' => 'Pagina unica.',
            'base_cost' => 400,
            'monthly_cost' => 0,
            'multiplier' => 1,
            'currency' => 'GBP',
            'sort_order' => 1,
            'is_active' => true,
        ], $overrides));
    }
}
