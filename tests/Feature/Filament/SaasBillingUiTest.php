<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\BusinessResource;
use App\Filament\Resources\PlanResource;
use App\Filament\Resources\SubscriptionResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasBillingUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_saas_billing_lists_render_operational_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            BusinessResource::class => 'businesses',
            PlanResource::class => 'plans',
            SubscriptionResource::class => 'subscriptions',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.administration.{$translationKey}.list_subheading"));
        }

        $this->get(PlanResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText(__('admin.migration.administration.plans.empty_heading'));

        $this->get(SubscriptionResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText(__('admin.migration.administration.subscriptions.empty_heading'));
    }
}
