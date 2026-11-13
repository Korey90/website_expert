<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private array $newPermissions = [
        'view_calendar',
        'manage_calendar',
        'view_domains',
        'manage_domain_pricing',
    ];

    /** @var list<string> */
    private array $managerPlatformPermissions = [
        'manage_roles', 'manage_permissions',
        'view_users', 'create_users', 'edit_users', 'delete_users',
        'view_businesses', 'edit_businesses',
        'view_plans', 'create_plans', 'edit_plans', 'delete_plans', 'view_subscriptions',
        'view_sessions', 'revoke_sessions',
        'manage_pipeline', 'manage_project_templates', 'manage_domain_pricing',
        'export_leads', 'view_security_events', 'manage_security_events',
        'close_finance_periods', 'submit_tax_returns',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->newPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();
        $admin?->givePermissionTo($this->newPermissions);

        $manager = Role::query()->where('name', 'manager')->where('guard_name', 'web')->first();
        $manager?->givePermissionTo(['view_calendar', 'manage_calendar', 'view_domains']);
        $manager?->revokePermissionTo(
            Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $this->managerPlatformPermissions)
                ->get()
        );

        $developer = Role::query()->where('name', 'developer')->where('guard_name', 'web')->first();
        $developer?->givePermissionTo(['view_calendar', 'view_domains']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->newPermissions)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
