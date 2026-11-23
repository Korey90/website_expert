<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\AccountProfilePage;
use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\BasePage;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\NotificationResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Support\FilamentPermissionRegistry;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Filament\Notifications\DatabaseNotification;
use Filament\Pages\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminAccessCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
    }

    public function test_every_filament_resource_and_page_has_a_seeded_access_key(): void
    {
        foreach ($this->resourceClasses() as $resource) {
            $this->assertTrue(
                is_subclass_of($resource, BaseResource::class),
                "Resource [{$resource}] must extend BaseResource."
            );

            $this->assertSeededPermission(
                FilamentPermissionRegistry::resourcePermission($resource, 'viewAny'),
                "Resource [{$resource}] has no view permission."
            );

            foreach (array_keys($resource::getPages()) as $pageKey) {
                $action = match ($pageKey) {
                    'index', 'view' => 'viewAny',
                    'create' => 'create',
                    'edit', 'tasks' => 'update',
                    default => null,
                };

                $this->assertNotNull($action, "Resource [{$resource}] has an unclassified page [{$pageKey}].");
                $this->assertSeededPermission(
                    FilamentPermissionRegistry::resourcePermission($resource, $action),
                    "Resource page [{$resource}:{$pageKey}] has no permission."
                );
            }
        }

        foreach ($this->pageClasses() as $page) {
            if ($page !== AdminDashboard::class) {
                $this->assertTrue(
                    is_subclass_of($page, BasePage::class),
                    "Page [{$page}] must extend BasePage."
                );
            }

            $this->assertSeededPermission(
                FilamentPermissionRegistry::pagePermission($page),
                "Page [{$page}] has no permission."
            );
        }
    }

    public function test_every_seeded_permission_is_available_in_the_role_editor(): void
    {
        $groups = RoleResource::permissionGroups();

        foreach (Permission::query()->pluck('name') as $permission) {
            $visible = collect($groups)->contains(
                fn (array $group): bool => RoleResource::permissionMatchesGroup($permission, $group)
            );

            $this->assertTrue($visible, "Permission [{$permission}] is missing from the role editor.");
        }
    }

    public function test_panel_access_alone_does_not_expose_modules_or_navigation(): void
    {
        $user = $this->tenantUser('panel-only@example.test');
        $user->givePermissionTo('access_admin_panel');
        $this->actingAs($user);

        $this->assertTrue(AdminDashboard::canAccess());
        $this->assertTrue(AccountProfilePage::canAccess());

        foreach ($this->resourceClasses() as $resource) {
            $this->assertFalse($resource::canAccess(), "Resource [{$resource}] leaked with panel-only access.");
        }

        foreach ($this->pageClasses() as $page) {
            if (in_array($page, [AdminDashboard::class, AccountProfilePage::class], true)) {
                continue;
            }

            $this->assertFalse($page::canAccess(), "Page [{$page}] leaked with panel-only access.");
        }
    }

    public function test_platform_admin_navigation_contains_every_registered_module(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        foreach ($this->resourceClasses() as $resource) {
            $this->assertTrue($resource::canAccess(), "Admin cannot access resource [{$resource}].");
        }

        foreach ($this->pageClasses() as $page) {
            $this->assertTrue($page::canAccess(), "Admin cannot access page [{$page}].");
        }
    }

    public function test_manager_is_tenant_scoped_and_has_no_platform_operator_permissions(): void
    {
        $manager = $this->tenantUser('manager-scope@example.test');
        $manager->assignRole('manager');

        foreach ([
            'view_businesses', 'edit_businesses',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_plans', 'create_plans', 'edit_plans', 'delete_plans', 'view_subscriptions',
            'view_sessions', 'revoke_sessions',
            'manage_roles', 'manage_permissions', 'manage_domain_pricing',
        ] as $permission) {
            $this->assertFalse($manager->can($permission), "Manager unexpectedly has [{$permission}].");
        }
    }

    public function test_tenant_resources_keep_the_business_scope_for_a_manager(): void
    {
        $manager = $this->tenantUser('manager-query-scope@example.test');
        $manager->assignRole('manager');
        $this->actingAs($manager);

        $businessId = $manager->currentBusiness()?->getKey();
        $this->assertNotNull($businessId);

        foreach ($this->resourceClasses() as $resource) {
            $model = $resource::getModel();

            if (! in_array(BelongsToTenant::class, class_uses_recursive($model), true)) {
                continue;
            }

            $bindings = $resource::getEloquentQuery()->getBindings();
            $this->assertContains(
                $businessId,
                $bindings,
                "Tenant resource [{$resource}] does not bind the active business."
            );
        }
    }

    public function test_custom_admin_routes_declare_their_module_permissions(): void
    {
        $expectations = [
            'lead-notes.unpin' => ['can:edit_leads'],
            'admin.email-preview' => ['can:view_email_templates'],
            'admin.finance.attachments.preview' => ['can:view_finance'],
            'admin.finance.attachments.download' => ['can:view_finance'],
            'admin.finance.expense-report.csv' => ['can:view_finance'],
            'admin.finance.vat-returns.accountant-pack' => ['can:view_finance'],
            'admin.finance.vat-returns.hmrc-receipt' => ['can:submit_tax_returns'],
            'admin.finance.universal-credit.evidence-packs.download' => ['can:export_universal_credit_pack'],
            'admin.finance.universal-credit.assessment-periods.report' => ['can:export_universal_credit_pack'],
            'reports.leads.csv' => ['can:view_reports', 'can:export_reports'],
            'admin.domain-price-list.export' => ['can:manage_domain_pricing'],
            'admin.google-calendar.connect' => ['can:manage_calendar'],
            'admin.calendar.events' => ['can:view_calendar'],
            'admin.hmrc.oauth.connect' => ['can:view_finance'],
        ];

        foreach ($expectations as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route [{$name}] is not registered.");

            foreach ($middleware as $expected) {
                $this->assertContains($expected, $route->gatherMiddleware(), "Route [{$name}] lacks [{$expected}].");
            }
        }
    }

    public function test_notification_recipients_and_records_are_tenant_scoped(): void
    {
        $manager = $this->tenantUser('notification-manager@example.test');
        $manager->assignRole('manager');

        $otherBusiness = Business::create([
            'name' => 'Other Tenant',
            'slug' => 'notification-other-tenant',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create(['email' => 'notification-other@example.test']);
        BusinessUser::create([
            'business_id' => $otherBusiness->getKey(),
            'user_id' => $otherUser->getKey(),
            'role' => 'member',
            'is_active' => true,
        ]);

        $manager->notifications()->create($this->notificationPayload('Manager notification'));
        $otherUser->notifications()->create($this->notificationPayload('Other notification'));

        $this->actingAs($manager);

        $recipientIds = NotificationResource::recipientQuery()->pluck('users.id')->all();
        $this->assertContains($manager->getKey(), $recipientIds);
        $this->assertNotContains($otherUser->getKey(), $recipientIds);
        $this->assertSame(1, NotificationResource::getEloquentQuery()->count());
        $this->assertSame($manager->getKey(), NotificationResource::getEloquentQuery()->value('notifiable_id'));
    }

    /** @return list<class-string> */
    private function resourceClasses(): array
    {
        return collect(glob(app_path('Filament/Resources/*Resource.php')) ?: [])
            ->reject(fn (string $path): bool => basename($path) === 'BaseResource.php')
            ->map(fn (string $path): string => 'App\\Filament\\Resources\\'.pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<class-string> */
    private function pageClasses(): array
    {
        return collect(glob(app_path('Filament/Pages/*.php')) ?: [])
            ->reject(fn (string $path): bool => basename($path) === 'BasePage.php')
            ->map(fn (string $path): string => 'App\\Filament\\Pages\\'.pathinfo($path, PATHINFO_FILENAME))
            ->filter(fn (string $class): bool => class_exists($class) && is_subclass_of($class, Page::class))
            ->sort()
            ->values()
            ->all();
    }

    private function assertSeededPermission(?string $permission, string $message): void
    {
        $this->assertNotNull($permission, $message);
        $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
    }

    private function tenantUser(string $email): User
    {
        $business = Business::query()->where('slug', 'website-expert')->firstOrFail();
        $user = User::factory()->create(['email' => $email, 'is_active' => true]);

        BusinessUser::create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $user;
    }

    /** @return array<string, mixed> */
    private function notificationPayload(string $title): array
    {
        return [
            'id' => (string) Str::orderedUuid(),
            'type' => DatabaseNotification::class,
            'data' => ['title' => $title, 'format' => 'filament'],
            'read_at' => null,
        ];
    }
}
