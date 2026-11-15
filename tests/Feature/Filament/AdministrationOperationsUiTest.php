<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\JobManagerPage;
use App\Filament\Pages\LogViewerPage;
use App\Filament\Resources\SecurityEventResource;
use App\Models\SecurityEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AdministrationOperationsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_operator_pages_render_operational_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $logPath = storage_path('logs/zz-admin-ui-test.log');
        file_put_contents($logPath, "[2026-11-15 10:00:00] local.INFO: Admin UI test entry\n");

        try {
            $this->actingAs($admin)
                ->get(SecurityEventResource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__('admin.migration.administration.security.list_subheading'))
                ->assertSeeText(__('admin.migration.administration.security.empty_heading'));

            $this->get(LogViewerPage::getUrl())
                ->assertOk()
                ->assertSeeText(__('admin.migration.administration.log_viewer.subheading'))
                ->assertSeeText(__('admin.migration.administration.log_viewer.filters_heading'))
                ->assertSeeText('Admin UI test entry');

            $this->get(JobManagerPage::getUrl())
                ->assertOk()
                ->assertSeeText(__('jobs.subheading'))
                ->assertSeeText(__('jobs.tab_failed'))
                ->assertSeeText(__('jobs.worker'));
        } finally {
            @unlink($logPath);
        }
    }

    public function test_security_viewer_cannot_see_mutating_actions_or_operator_pages(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(['access_admin_panel', 'view_security_events']);

        SecurityEvent::query()->create([
            'ip' => '203.0.113.10',
            'jail' => 'sshd',
            'attack_type' => 'Brute force SSH',
            'failures' => 6,
            'action' => 'banned',
            'banned_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(SecurityEventResource::getUrl('index'))
            ->assertOk()
            ->assertDontSeeText(__('admin.migration.administration.security.unban'))
            ->assertDontSeeText(__('admin.migration.administration.security.report'));

        $this->get(LogViewerPage::getUrl())->assertForbidden();
        $this->get(JobManagerPage::getUrl())->assertForbidden();
    }

    public function test_operator_translations_have_matching_keys(): void
    {
        foreach (['admin', 'jobs'] as $file) {
            $english = array_keys(Arr::dot(require lang_path("en/{$file}.php")));

            foreach (['pl', 'pt'] as $locale) {
                $translated = array_keys(Arr::dot(require lang_path("{$locale}/{$file}.php")));

                $this->assertSame($english, $translated, "Translation keys differ for {$locale}/{$file}.php");
            }
        }
    }
}
