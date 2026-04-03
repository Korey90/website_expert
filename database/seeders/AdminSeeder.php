<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use App\Models\ProjectTemplate;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\BusinessProfile;
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
            // CRM — ClientResource
            'view_clients', 'create_clients', 'edit_clients', 'delete_clients',
            // CRM — LeadResource / PipelinePage
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            // CRM — ContractResource
            'view_contracts', 'create_contracts', 'edit_contracts', 'delete_contracts',
            // Finance — QuoteResource
            'view_quotes', 'create_quotes', 'edit_quotes', 'delete_quotes',
            // Finance — InvoiceResource
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            // Projects — ProjectResource
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            // Templates — ContractTemplateResource
            'view_contract_templates', 'create_contract_templates', 'edit_contract_templates', 'delete_contract_templates',
            // Templates — EmailTemplateResource
            'view_email_templates', 'create_email_templates', 'edit_email_templates', 'delete_email_templates',
            // Templates — SmsTemplateResource
            'view_sms_templates', 'create_sms_templates', 'edit_sms_templates', 'delete_sms_templates',
            // Automations — AutomationRuleResource
            'view_automations', 'create_automations', 'edit_automations', 'delete_automations',
            // Website CMS — PageResource
            'view_pages', 'create_pages', 'edit_pages', 'delete_pages',
            // Website CMS — SiteSectionResource
            'view_site_sections', 'create_site_sections', 'edit_site_sections', 'delete_site_sections',
            // Users — UserResource
            'view_users', 'create_users', 'edit_users', 'delete_users',
            // Reports
            'view_reports', 'export_reports',
            // System Settings — IntegrationSettingsPage, LegalSettingsPage, TrackingSettingsPage
            'manage_settings',
            // System Settings — RoleResource
            'manage_roles',
            // System Config — PipelineStageResource
            'manage_pipeline',
            // System Config — CalculatorPricingResource
            'manage_calculator',
            // System Config — ProjectTemplateResource
            'manage_project_templates',
            // Business Profile
            'manage_business_profile',
            'view_business_settings',
            'manage_business_settings',
            // Landing Pages
            'view_landing_pages',
            'manage_landing_pages',
            'publish_landing_pages',
            'generate_landing_pages_ai',
            // Lead Capture
            'manage_leads',
            'delete_leads',
            'export_leads',
            'manage_api_tokens',
            'view_lead_sources',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Admin gets all
        $admin->syncPermissions($permissions);

        // Manager: full CRM/Finance/Projects access; no role/user admin
        $managerExclude = ['manage_roles', 'delete_users', 'manage_pipeline', 'manage_project_templates', 'export_leads'];
        $manager->syncPermissions(array_values(array_filter($permissions, fn ($p) => ! in_array($p, $managerExclude))));

        // Developer: read-only on CRM/Finance + edit projects/contracts
        $developer->syncPermissions([
            'view_clients',
            'view_leads',
            'view_quotes',
            'view_invoices',
            'view_contracts',
            'view_projects', 'edit_projects',
            'view_business_settings',
            'view_landing_pages',
            'view_lead_sources',
        ]);

        // ---------------------------------------------------
        // Default admin user
        // ---------------------------------------------------
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@websiteexpert.co.uk'],
            [
                'name'      => 'Admin WebsiteExpert',
                'password'  => bcrypt('password'), // Change this to a secure password in production!
                'is_active' => true,
                'locale'    => 'en',
            ]
        );
        $adminUser->assignRole('admin');

        // ---------------------------------------------------
        // Default Business (tenant root for WebsiteExpert agency)
        // ---------------------------------------------------
        $business = Business::firstOrCreate(
            ['slug' => 'website-expert'],
            [
                'name'      => 'WebsiteExpert Ltd',
                'locale'    => 'en',
                'timezone'  => 'Europe/London',
                'plan'      => 'pro',
                'is_active' => true,
            ]
        );

        BusinessUser::firstOrCreate(
            ['business_id' => $business->id, 'user_id' => $adminUser->id],
            ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]
        );

        BusinessProfile::firstOrCreate(['business_id' => $business->id]);

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
                    ['name' => 'Discovery & Brief', 'order' => 1, 'tasks' => [
                        ['title' => 'Send client questionnaire',        'priority' => 'high'],
                        ['title' => 'Gather brand assets (logo, fonts, colours)', 'priority' => 'high'],
                        ['title' => 'Competitor research (3 examples)', 'priority' => 'medium'],
                        ['title' => 'Define sitemap & page list',       'priority' => 'medium'],
                    ]],
                    ['name' => 'Design Mockups', 'order' => 2, 'tasks' => [
                        ['title' => 'Create homepage wireframe',        'priority' => 'high'],
                        ['title' => 'Design homepage mockup (desktop)', 'priority' => 'high'],
                        ['title' => 'Design homepage mockup (mobile)',  'priority' => 'medium'],
                        ['title' => 'Client review & collect feedback', 'priority' => 'medium'],
                        ['title' => 'Apply revisions',                  'priority' => 'medium'],
                    ]],
                    ['name' => 'Development', 'order' => 3, 'tasks' => [
                        ['title' => 'Set up hosting & development environment', 'priority' => 'high'],
                        ['title' => 'Build homepage',                   'priority' => 'high'],
                        ['title' => 'Build inner pages (About, Services, Contact)', 'priority' => 'high'],
                        ['title' => 'Implement contact form',           'priority' => 'medium'],
                        ['title' => 'Ensure mobile responsiveness',     'priority' => 'high'],
                    ]],
                    ['name' => 'Content Integration', 'order' => 4, 'tasks' => [
                        ['title' => 'Add client copy to all pages',     'priority' => 'high'],
                        ['title' => 'Optimise & upload images',         'priority' => 'medium'],
                        ['title' => 'Set SEO meta titles & descriptions', 'priority' => 'medium'],
                        ['title' => 'Set up Google Analytics / Tag Manager', 'priority' => 'medium'],
                    ]],
                    ['name' => 'Testing & QA', 'order' => 5, 'tasks' => [
                        ['title' => 'Cross-browser testing (Chrome, Firefox, Safari)', 'priority' => 'high'],
                        ['title' => 'Mobile & tablet testing',          'priority' => 'high'],
                        ['title' => 'Test contact form submissions',    'priority' => 'high'],
                        ['title' => 'Run Google PageSpeed Insights',    'priority' => 'medium'],
                        ['title' => 'Fix any issues found',             'priority' => 'high'],
                    ]],
                    ['name' => 'Launch & Handover', 'order' => 6, 'tasks' => [
                        ['title' => 'Point DNS / go live',              'priority' => 'high'],
                        ['title' => 'Verify SSL certificate',           'priority' => 'high'],
                        ['title' => 'Submit sitemap to Google Search Console', 'priority' => 'medium'],
                        ['title' => 'Send handover document to client', 'priority' => 'medium'],
                        ['title' => 'Request client sign-off',          'priority' => 'medium'],
                    ]],
                ],
            ],
            [
                'name'         => 'E-Commerce Store',
                'service_type' => 'ecommerce',
                'description'  => 'Full WooCommerce / Shopify store',
                'phases'       => [
                    ['name' => 'Discovery & Strategy', 'order' => 1, 'tasks' => [
                        ['title' => 'Define product catalogue & categories', 'priority' => 'high'],
                        ['title' => 'Agree platform (WooCommerce / Shopify)', 'priority' => 'high'],
                        ['title' => 'Competitor analysis',              'priority' => 'medium'],
                        ['title' => 'Gather brand assets',              'priority' => 'high'],
                    ]],
                    ['name' => 'UX / Wireframes', 'order' => 2, 'tasks' => [
                        ['title' => 'Create full sitemap',              'priority' => 'high'],
                        ['title' => 'Wireframe homepage',               'priority' => 'high'],
                        ['title' => 'Wireframe product listing & detail pages', 'priority' => 'high'],
                        ['title' => 'Wireframe cart & checkout flow',   'priority' => 'high'],
                    ]],
                    ['name' => 'Design', 'order' => 3, 'tasks' => [
                        ['title' => 'Design homepage (desktop + mobile)', 'priority' => 'high'],
                        ['title' => 'Design product listing page',      'priority' => 'high'],
                        ['title' => 'Design product detail page',       'priority' => 'high'],
                        ['title' => 'Design cart & checkout',           'priority' => 'high'],
                        ['title' => 'Client approval of designs',       'priority' => 'medium'],
                    ]],
                    ['name' => 'Development', 'order' => 4, 'tasks' => [
                        ['title' => 'Set up hosting & platform installation', 'priority' => 'high'],
                        ['title' => 'Theme / template setup',           'priority' => 'high'],
                        ['title' => 'Build product catalogue structure', 'priority' => 'high'],
                        ['title' => 'Custom homepage & inner pages dev', 'priority' => 'high'],
                        ['title' => 'Configure shipping zones & rates', 'priority' => 'medium'],
                    ]],
                    ['name' => 'Product Import', 'order' => 5, 'tasks' => [
                        ['title' => 'Prepare product CSV / data file',  'priority' => 'high'],
                        ['title' => 'Import products',                  'priority' => 'high'],
                        ['title' => 'Verify product images & descriptions', 'priority' => 'medium'],
                        ['title' => 'Set up product variants & stock',  'priority' => 'medium'],
                    ]],
                    ['name' => 'Payment Integration', 'order' => 6, 'tasks' => [
                        ['title' => 'Configure Stripe / PayPal gateway', 'priority' => 'high'],
                        ['title' => 'Test transactions (sandbox mode)',  'priority' => 'high'],
                        ['title' => 'Set up order confirmation emails', 'priority' => 'medium'],
                        ['title' => 'Configure VAT / tax rules',        'priority' => 'medium'],
                    ]],
                    ['name' => 'Testing & QA', 'order' => 7, 'tasks' => [
                        ['title' => 'Full checkout flow test (guest + account)', 'priority' => 'high'],
                        ['title' => 'Mobile & tablet testing',          'priority' => 'high'],
                        ['title' => 'Cross-browser testing',            'priority' => 'medium'],
                        ['title' => 'Performance & image optimisation', 'priority' => 'medium'],
                        ['title' => 'Fix all issues',                   'priority' => 'high'],
                    ]],
                    ['name' => 'SEO & Analytics Setup', 'order' => 8, 'tasks' => [
                        ['title' => 'Set up Google Analytics 4',        'priority' => 'medium'],
                        ['title' => 'Set up Google Search Console',     'priority' => 'medium'],
                        ['title' => 'Install SEO plugin & configure',   'priority' => 'medium'],
                        ['title' => 'Add meta titles & descriptions to key pages', 'priority' => 'medium'],
                    ]],
                    ['name' => 'Launch', 'order' => 9, 'tasks' => [
                        ['title' => 'Point DNS & verify SSL',           'priority' => 'high'],
                        ['title' => 'Switch payment gateway to live mode', 'priority' => 'high'],
                        ['title' => 'Submit sitemap to Google',         'priority' => 'medium'],
                        ['title' => 'Monitor first 48h for errors',     'priority' => 'medium'],
                        ['title' => 'Send handover & request sign-off', 'priority' => 'medium'],
                    ]],
                ],
            ],
            [
                'name'         => 'Web Application',
                'service_type' => 'aplikacja',
                'description'  => 'Custom SPA / Laravel app',
                'phases'       => [
                    ['name' => 'Discovery & Requirements', 'order' => 1, 'tasks' => [
                        ['title' => 'Collect & document functional requirements', 'priority' => 'high'],
                        ['title' => 'Write user stories',               'priority' => 'high'],
                        ['title' => 'Define MVP scope',                 'priority' => 'high'],
                        ['title' => 'Agree on tech stack',              'priority' => 'high'],
                    ]],
                    ['name' => 'System Architecture', 'order' => 2, 'tasks' => [
                        ['title' => 'Design database schema (ERD)',     'priority' => 'high'],
                        ['title' => 'Define API endpoints',             'priority' => 'high'],
                        ['title' => 'Plan infrastructure & hosting',    'priority' => 'medium'],
                        ['title' => 'Set up project repository & CI/CD skeleton', 'priority' => 'medium'],
                    ]],
                    ['name' => 'UI/UX Design', 'order' => 3, 'tasks' => [
                        ['title' => 'Create wireframes for key screens', 'priority' => 'high'],
                        ['title' => 'Build UI component library / design system', 'priority' => 'high'],
                        ['title' => 'Design high-fidelity prototypes',  'priority' => 'high'],
                        ['title' => 'Client approval of designs',       'priority' => 'medium'],
                    ]],
                    ['name' => 'Backend Development', 'order' => 4, 'tasks' => [
                        ['title' => 'Set up Laravel project & auth',    'priority' => 'high'],
                        ['title' => 'Build database migrations & models', 'priority' => 'high'],
                        ['title' => 'Implement core API endpoints',     'priority' => 'high'],
                        ['title' => 'Write unit tests for core logic',  'priority' => 'medium'],
                        ['title' => 'Set up queues & background jobs',  'priority' => 'medium'],
                    ]],
                    ['name' => 'Frontend Development', 'order' => 5, 'tasks' => [
                        ['title' => 'Scaffold frontend app (React / Vue / Blade)', 'priority' => 'high'],
                        ['title' => 'Build core UI components',         'priority' => 'high'],
                        ['title' => 'Integrate with backend API',       'priority' => 'high'],
                        ['title' => 'Implement auth flow (login, register, etc.)', 'priority' => 'high'],
                        ['title' => 'Ensure responsive design',         'priority' => 'medium'],
                    ]],
                    ['name' => 'Integration & API', 'order' => 6, 'tasks' => [
                        ['title' => 'Integrate 3rd-party services (email, payments, etc.)', 'priority' => 'high'],
                        ['title' => 'Set up webhooks where needed',     'priority' => 'medium'],
                        ['title' => 'API documentation (Postman / Swagger)', 'priority' => 'medium'],
                    ]],
                    ['name' => 'Testing & QA', 'order' => 7, 'tasks' => [
                        ['title' => 'End-to-end testing of all user flows', 'priority' => 'high'],
                        ['title' => 'Security review (XSS, CSRF, SQL injection)', 'priority' => 'urgent'],
                        ['title' => 'Performance & load testing',       'priority' => 'medium'],
                        ['title' => 'Cross-browser & mobile testing',   'priority' => 'medium'],
                        ['title' => 'Fix all issues found',             'priority' => 'high'],
                    ]],
                    ['name' => 'Deployment', 'order' => 8, 'tasks' => [
                        ['title' => 'Provision production server',      'priority' => 'high'],
                        ['title' => 'Configure CI/CD pipeline',         'priority' => 'high'],
                        ['title' => 'Deploy to production & smoke test', 'priority' => 'high'],
                        ['title' => 'Set up monitoring & error tracking (Sentry)', 'priority' => 'medium'],
                        ['title' => 'Configure backups',                'priority' => 'medium'],
                    ]],
                    ['name' => 'Documentation', 'order' => 9, 'tasks' => [
                        ['title' => 'Write technical documentation',    'priority' => 'medium'],
                        ['title' => 'Write user / admin guide',         'priority' => 'medium'],
                        ['title' => 'Record walkthrough video (optional)', 'priority' => 'low'],
                        ['title' => 'Send handover pack & request sign-off', 'priority' => 'medium'],
                    ]],
                ],
            ],
        ];

        foreach ($templates as $tmpl) {
            ProjectTemplate::updateOrCreate(['name' => $tmpl['name']], $tmpl);
        }
    }
}
