<?php

namespace Tests\Feature\Marketing;

use App\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePriceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_prices_are_visible_by_default_for_existing_services_section(): void
    {
        $this->makeServicesSection([]);

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('services.extra.show_prices', null)
        );

        $this->get('/services')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Services/Index')
            ->where('showPrices', true)
        );
    }

    public function test_disabling_prices_updates_both_homepage_and_services_index(): void
    {
        $this->makeServicesSection(['show_prices' => false]);

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('services.extra.show_prices', false)
        );

        $this->get('/services')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Services/Index')
            ->where('showPrices', false)
        );
    }

    public function test_enabling_prices_updates_both_homepage_and_services_index(): void
    {
        $this->makeServicesSection(['show_prices' => true]);

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('services.extra.show_prices', true)
        );

        $this->get('/services')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Services/Index')
            ->where('showPrices', true)
        );
    }

    private function makeServicesSection(array $extra): void
    {
        SiteSection::create([
            'key' => 'services',
            'label' => 'Services',
            'is_active' => true,
            'extra' => $extra,
        ]);
    }
}
