<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AutomationLogResource;
use App\Filament\Resources\AutomationRuleResource;
use App\Filament\Resources\AutomationTriggerResource;
use App\Models\AutomationRule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_automation_lists_render_context_and_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            AutomationRuleResource::class => 'rules',
            AutomationTriggerResource::class => 'triggers',
            AutomationLogResource::class => 'logs',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.automation.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.automation.{$translationKey}.empty_heading"));
        }
    }

    public function test_view_only_user_does_not_see_mutating_automation_actions(): void
    {
        $rule = AutomationRule::create([
            'name' => 'Permission test rule',
            'trigger_event' => 'lead.created',
            'conditions' => [],
            'actions' => [],
            'delay_minutes' => 0,
            'is_active' => true,
        ]);
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo([
            'access_admin_panel',
            'view_automations',
            'view_automation_logs',
        ]);

        $this->actingAs($viewer)
            ->get(AutomationRuleResource::getUrl('view', ['record' => $rule]))
            ->assertOk()
            ->assertDontSeeText('Test Rule');

        $this->get(AutomationLogResource::getUrl('index'))
            ->assertOk()
            ->assertDontSeeText(__('admin.migration.automation.logs.delete_old_label'));

    }

    public function test_admin_sees_mutating_automation_actions(): void
    {
        $rule = AutomationRule::create([
            'name' => 'Permission test rule',
            'trigger_event' => 'lead.created',
            'conditions' => [],
            'actions' => [],
            'delay_minutes' => 0,
            'is_active' => true,
        ]);
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(AutomationRuleResource::getUrl('view', ['record' => $rule]))
            ->assertOk()
            ->assertSeeText('Test Rule');

        $this->get(AutomationLogResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText(__('admin.migration.automation.logs.delete_old_label'));
    }
}
