<?php

namespace Tests\Unit\Billing;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\Billing\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
        PlanService::clearCache();
    }

    public function test_get_plans_returns_requested_currency_prices(): void
    {
        $plan = $this->createPlan('basic');
        $plan->planPrices()->create([
            'currency' => 'GBP',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 2900,
            'is_active' => true,
        ]);
        $plan->planPrices()->create([
            'currency' => 'PLN',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 14500,
            'stripe_price_id' => 'price_basic_pln_monthly',
            'is_active' => true,
        ]);

        $plans = PlanService::getPlans('PLN');

        $this->assertSame('PLN', $plans['basic']['currency']);
        $this->assertSame(14500, $plans['basic']['price_monthly']);
        $this->assertSame('price_basic_pln_monthly', $plans['basic']['stripe_price_id_monthly']);
    }

    public function test_get_plans_falls_back_to_default_currency_when_requested_price_is_missing(): void
    {
        $plan = $this->createPlan('pro');
        $plan->planPrices()->create([
            'currency' => 'GBP',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 4900,
            'is_active' => true,
        ]);

        $plans = PlanService::getPlans('PLN');

        $this->assertSame('GBP', $plans['pro']['currency']);
        $this->assertSame(4900, $plans['pro']['price_monthly']);
    }

    public function test_get_checkout_price_returns_matching_currency_price(): void
    {
        $plan = $this->createPlan('agency');
        $plan->planPrices()->create([
            'currency' => 'PLN',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 74500,
            'stripe_price_id' => 'price_agency_pln_monthly',
            'is_active' => true,
        ]);

        $price = PlanService::getCheckoutPrice('agency', 'PLN');

        $this->assertNotNull($price);
        $this->assertSame('PLN', $price->currency);
        $this->assertSame('price_agency_pln_monthly', PlanService::stripePriceIdFor($price));
    }

    public function test_find_plan_slug_by_stripe_price_id_uses_plan_prices(): void
    {
        $plan = $this->createPlan('pro');
        $plan->planPrices()->create([
            'currency' => 'EUR',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 5880,
            'stripe_price_id' => 'price_pro_eur_monthly',
            'is_active' => true,
        ]);

        $this->assertSame('pro', PlanService::findPlanSlugByStripePriceId('price_pro_eur_monthly'));
    }

    public function test_stripe_price_id_can_fall_back_to_config(): void
    {
        config(['services.stripe.prices.basic.PLN.monthly' => 'price_basic_pln_config']);

        $plan = $this->createPlan('basic');
        $price = $plan->planPrices()->create([
            'currency' => 'PLN',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 14500,
            'stripe_price_id' => null,
            'is_active' => true,
        ]);

        $this->assertSame('price_basic_pln_config', PlanService::stripePriceIdFor($price));
        $this->assertSame('basic', PlanService::findPlanSlugByStripePriceId('price_basic_pln_config'));
        $this->assertSame($price->id, PlanService::findPlanPriceByStripePriceId('price_basic_pln_config')?->id);
    }

    private function createPlan(string $slug): Plan
    {
        return Plan::create([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'description' => null,
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_landing_pages' => 5,
            'max_ai_per_month' => 10,
            'multi_user' => false,
            'custom_domain' => false,
            'ab_testing' => false,
            'features' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
