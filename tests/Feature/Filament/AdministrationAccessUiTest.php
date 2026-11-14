<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SessionResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationAccessUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_access_administration_lists_render_operational_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            UserResource::class => 'users',
            RoleResource::class => 'roles',
            PermissionResource::class => 'permissions',
            SessionResource::class => 'sessions',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.administration.{$translationKey}.list_subheading"));
        }

        $this->get(SessionResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText(__('admin.migration.administration.sessions.empty_heading'));
    }

    public function test_session_viewer_does_not_see_revocation_actions(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(['access_admin_panel', 'view_sessions']);

        $this->actingAs($viewer)
            ->get(SessionResource::getUrl('index'))
            ->assertOk()
            ->assertDontSeeText(__('admin.migration.administration.sessions.flush_inactive_label'))
            ->assertDontSeeText(__('admin.migration.administration.sessions.revoke_guests_label'));
    }

    public function test_admin_sees_session_revocation_actions(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(SessionResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText(__('admin.migration.administration.sessions.flush_inactive_label'))
            ->assertSeeText(__('admin.migration.administration.sessions.revoke_guests_label'));
    }
}
