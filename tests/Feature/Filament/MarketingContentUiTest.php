<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PortfolioProjectResource;
use App\Filament\Resources\ServiceItemResource;
use App\Filament\Resources\ServicePageResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingContentUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_services_and_portfolio_render_context_and_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            ServiceItemResource::class => 'services',
            ServicePageResource::class => 'service_pages',
            PortfolioProjectResource::class => 'portfolio',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.empty_heading"));
        }
    }
}
