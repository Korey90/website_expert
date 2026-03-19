<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------
        // Roles
        // ---------------------------------------------------
        $admin     = Role::firstOrCreate(['name' => 'admin',     'guard_name' => 'web']);
        $manager   = Role::firstOrCreate(['name' => 'manager',   'guard_name' => 'web']);
        $developer = Role::firstOrCreate(['name' => 'developer', 'guard_name' => 'web']);
        $client    = Role::firstOrCreate(['name' => 'client',    'guard_name' => 'web']);

        // ---------------------------------------------------
        // Permissions
        // ---------------------------------------------------
        $permissions = [
            // clients
            'view_clients', 'create_clients', 'edit_clients', 'delete_clients',
            // leads
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            // projects
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            // invoices
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            // quotes
            'view_quotes', 'create_quotes', 'edit_quotes', 'delete_quotes',
            // users
            'view_users', 'create_users', 'edit_users', 'delete_users',
            // reports
            'view_reports', 'export_reports',
            // settings
            'manage_settings', 'manage_roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin gets all
        $admin->syncPermissions($permissions);

        // Manager: everything except user/role management
        $manager->syncPermissions(array_filter($permissions, fn ($p) => ! in_array($p, ['manage_roles', 'delete_users'])));

        // Developer: projects + own tasks
        $developer->syncPermissions(['view_clients', 'view_projects', 'edit_projects', 'view_invoices']);

        // ---------------------------------------------------
        // Default admin user
        // ---------------------------------------------------
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@websiteexpert.co.uk'],
            [
                'name'      => 'Admin WebsiteExpert',
                'password'  => bcrypt('Admin@WebsiteExpert2026!'),
                'is_active' => true,
                'locale'    => 'en',
            ]
        );
        $adminUser->assignRole('admin');

        // ---------------------------------------------------
        // Default pipeline stages
        // ---------------------------------------------------
        $stages = [
            ['name' => 'New Lead',        'slug' => 'new-lead',        'color' => '#6B7280', 'order' => 1],
            ['name' => 'Contacted',       'slug' => 'contacted',       'color' => '#3B82F6', 'order' => 2],
            ['name' => 'Proposal Sent',   'slug' => 'proposal-sent',   'color' => '#8B5CF6', 'order' => 3],
            ['name' => 'Negotiation',     'slug' => 'negotiation',     'color' => '#F59E0B', 'order' => 4],
            ['name' => 'Won',             'slug' => 'won',             'color' => '#10B981', 'order' => 5, 'is_won' => true],
            ['name' => 'Lost',            'slug' => 'lost',            'color' => '#EF4444', 'order' => 6, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            PipelineStage::firstOrCreate(['slug' => $stage['slug']], $stage);
        }

        // ---------------------------------------------------
        // Default project templates
        // ---------------------------------------------------
        $templates = [
            [
                'name'         => 'Business Card Website',
                'service_type' => 'wizytowka',
                'description'  => '5-page brochure site',
                'phases'       => [
                    ['name' => 'Discovery & Brief',        'order' => 1],
                    ['name' => 'Design Mockups',           'order' => 2],
                    ['name' => 'Development',              'order' => 3],
                    ['name' => 'Content Integration',      'order' => 4],
                    ['name' => 'Testing & QA',             'order' => 5],
                    ['name' => 'Launch & Handover',        'order' => 6],
                ],
            ],
            [
                'name'         => 'E-Commerce Store',
                'service_type' => 'ecommerce',
                'description'  => 'Full WooCommerce / Shopify store',
                'phases'       => [
                    ['name' => 'Discovery & Strategy',     'order' => 1],
                    ['name' => 'UX / Wireframes',          'order' => 2],
                    ['name' => 'Design',                   'order' => 3],
                    ['name' => 'Development',              'order' => 4],
                    ['name' => 'Product Import',           'order' => 5],
                    ['name' => 'Payment Integration',      'order' => 6],
                    ['name' => 'Testing & QA',             'order' => 7],
                    ['name' => 'SEO & Analytics Setup',    'order' => 8],
                    ['name' => 'Launch',                   'order' => 9],
                ],
            ],
            [
                'name'         => 'Web Application',
                'service_type' => 'aplikacja',
                'description'  => 'Custom SPA / Laravel app',
                'phases'       => [
                    ['name' => 'Discovery & Requirements', 'order' => 1],
                    ['name' => 'System Architecture',      'order' => 2],
                    ['name' => 'UI/UX Design',             'order' => 3],
                    ['name' => 'Backend Development',      'order' => 4],
                    ['name' => 'Frontend Development',     'order' => 5],
                    ['name' => 'Integration & API',        'order' => 6],
                    ['name' => 'Testing & QA',             'order' => 7],
                    ['name' => 'Deployment',               'order' => 8],
                    ['name' => 'Documentation',            'order' => 9],
                ],
            ],
        ];

        foreach ($templates as $tmpl) {
            ProjectTemplate::firstOrCreate(['name' => $tmpl['name']], $tmpl);
        }
    }
}
