<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\CalculatorAdminPage;
use App\Filament\Pages\IntegrationSettingsPage;
use App\Filament\Pages\LegalSettingsPage;
use App\Filament\Pages\PaymentSettingsPage;
use App\Filament\Pages\TrackingSettingsPage;
use App\Filament\Resources\CalculatorPricingResource;
use App\Filament\Resources\CalculatorStepsResource;
use App\Filament\Resources\CalculatorStringsResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationSettingsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_settings_pages_render_operational_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $pages = [
            IntegrationSettingsPage::class => 'integrations_subheading',
            PaymentSettingsPage::class => 'payments_subheading',
            TrackingSettingsPage::class => 'tracking_subheading',
            LegalSettingsPage::class => 'legal_subheading',
        ];

        foreach ($pages as $page => $translationKey) {
            $this->get($page::getUrl())
                ->assertOk()
                ->assertSeeText(__("admin.migration.administration.settings.{$translationKey}"))
                ->assertSeeText(__('admin.migration.administration.settings.save'));
        }

        $this->get(CalculatorAdminPage::getUrl())
            ->assertOk()
            ->assertSeeText(__('admin.migration.administration.calculator.subheading'))
            ->assertSeeText(__('admin.migration.administration.calculator.steps_tab'))
            ->assertSeeText(__('admin.migration.administration.calculator.strings_tab'))
            ->assertSeeText(__('admin.migration.administration.calculator.pricing_tab'));
    }

    public function test_calculator_resource_lists_render_context_and_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            CalculatorStepsResource::class => 'steps',
            CalculatorStringsResource::class => 'strings',
            CalculatorPricingResource::class => 'pricing',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.administration.calculator.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.administration.calculator.{$translationKey}.empty_heading"));
        }
    }
}
