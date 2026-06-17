<?php

namespace Tests\Feature\Portal;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\ClientPortalAccess;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Services\Billing\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('en');
        PlanService::clearCache();
    }

    public function test_portal_only_client_is_redirected_away_from_billing(): void
    {
        $user = User::factory()->create();

        $client = Client::create([
            'company_name' => 'Agency Client Ltd',
            'primary_contact_email' => $user->email,
        ]);

        ClientPortalAccess::create(['client_id' => $client->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('portal.billing'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error', 'Workspace access is required for billing and plan management.');
    }

    public function test_workspace_member_can_open_billing_page(): void
    {
        [$user] = $this->workspaceUser();

        $this->actingAs($user)
            ->get(route('portal.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Billing/Index')
                ->where('business.name', 'Workspace Ltd')
            );
    }

    public function test_billing_page_uses_locale_currency_plan_prices(): void
    {
        app()->setLocale('pl');
        [$user] = $this->workspaceUser();

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
            'is_active' => true,
        ]);

        PlanService::clearCache();

        $this->actingAs($user)
            ->withSession(['locale' => 'pl'])
            ->get(route('portal.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('plans.0.key', 'basic')
                ->where('plans.0.currency', 'PLN')
                ->where('plans.0.price', 145)
            );
    }

    public function test_checkout_requires_stripe_price_for_locale_currency(): void
    {
        app()->setLocale('pl');
        [$user] = $this->workspaceUser();

        $plan = $this->createPlan('basic');
        $plan->planPrices()->create([
            'currency' => 'PLN',
            'interval' => PlanPrice::INTERVAL_MONTHLY,
            'amount_minor' => 14500,
            'stripe_price_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'pl'])
            ->from(route('portal.billing'))
            ->post(route('portal.billing.checkout', ['plan' => 'basic']), ['interval' => 'monthly'])
            ->assertRedirect(route('portal.billing'))
            ->assertSessionHasErrors('plan');
    }

    /**
     * @return array{0: User, 1: Business}
     */
    private function workspaceUser(): array
    {
        $user = User::factory()->create();

        $business = Business::create([
            'name' => 'Workspace Ltd',
            'slug' => 'workspace-ltd',
            'locale' => 'en',
            'timezone' => 'Europe/London',
            'plan' => 'free',
            'is_active' => true,
        ]);

        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [$user, $business];
    }

    private function createPlan(string $slug): Plan
    {
        return Plan::create([
            'slug' => $slug,
            'name' => ucfirst($slug),
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
