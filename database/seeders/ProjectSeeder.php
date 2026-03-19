<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $manager   = User::where('email', 'manager@websiteexpert.co.uk')->first();
        $developer = User::where('email', 'developer@websiteexpert.co.uk')->first();
        $developer2 = User::where('email', 'developer2@websiteexpert.co.uk')->first();

        $hargreaves = Client::where('company_name', 'Hargreaves & Sons Solicitors')->first();
        $oakfield   = Client::where('company_name', 'Oakfield Dental Practice')->first();
        $nts        = Client::where('company_name', 'Northern Trade Supplies Ltd')->first();
        $bloom      = Client::where('company_name', 'Bloom & Grow Garden Centre')->first();
        $coastal    = Client::where('company_name', 'Coastal Escapes Holiday Rentals')->first();

        $leadHargreaves = Lead::where('title', 'Website Redesign – Hargreaves Solicitors')->first();
        $leadNts        = Lead::where('title', 'E-Commerce Platform – Northern Trade Supplies')->first();
        $leadOakfield   = Lead::where('title', 'Dental Practice Website – Oakfield')->first();

        $projects = [
            // --- Completed ---
            [
                'title'        => 'Hargreaves Solicitors – Website Redesign',
                'client_id'    => $hargreaves?->id,
                'lead_id'      => $leadHargreaves?->id,
                'assigned_to'  => $developer?->id,
                'service_type' => 'wizytowka',
                'status'       => 'completed',
                'description'  => '12-page redesign of the solicitors website. Migrated from WordPress to Laravel. Fully responsive, WCAG 2.1 AA accessible.',
                'budget'       => 3800.00,
                'currency'     => 'GBP',
                'start_date'   => now()->subMonths(5)->toDateString(),
                'deadline'     => now()->subMonths(3)->toDateString(),
                'completed_at' => now()->subMonths(3)->addDays(5),
                'phases' => [
                    ['name' => 'Discovery & Brief',   'order' => 1, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Client kick-off meeting', 'status' => 'completed'],
                        ['title' => 'Content audit', 'status' => 'completed'],
                        ['title' => 'Brand guidelines review', 'status' => 'completed'],
                    ]],
                    ['name' => 'Design Mockups',      'order' => 2, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Homepage wireframe', 'status' => 'completed'],
                        ['title' => 'Interior page templates', 'status' => 'completed'],
                        ['title' => 'Client approval', 'status' => 'completed'],
                    ]],
                    ['name' => 'Development',         'order' => 3, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Laravel project setup', 'status' => 'completed'],
                        ['title' => 'Build homepage', 'status' => 'completed'],
                        ['title' => 'Build inner pages', 'status' => 'completed'],
                        ['title' => 'Contact form + GDPR', 'status' => 'completed'],
                    ]],
                    ['name' => 'Content Integration', 'order' => 4, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Copy paste all content', 'status' => 'completed'],
                        ['title' => 'Image optimisation', 'status' => 'completed'],
                    ]],
                    ['name' => 'Testing & QA',        'order' => 5, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Cross-browser tests', 'status' => 'completed'],
                        ['title' => 'Mobile responsiveness', 'status' => 'completed'],
                        ['title' => 'PageSpeed > 85', 'status' => 'completed'],
                    ]],
                    ['name' => 'Launch & Handover',   'order' => 6, 'status' => 'completed', 'tasks' => [
                        ['title' => 'DNS transfer', 'status' => 'completed'],
                        ['title' => 'SSL certificate', 'status' => 'completed'],
                        ['title' => 'Client training session', 'status' => 'completed'],
                    ]],
                ],
            ],
            // --- Active / In-Progress ---
            [
                'title'        => 'NTS Direct – E-Commerce Platform',
                'client_id'    => $nts?->id,
                'lead_id'      => $leadNts?->id,
                'assigned_to'  => $developer?->id,
                'service_type' => 'ecommerce',
                'status'       => 'active',
                'description'  => 'Full WooCommerce build with B2B pricing tiers, trade account portal, and product import from 3PL system.',
                'budget'       => 12500.00,
                'currency'     => 'GBP',
                'start_date'   => now()->subMonths(2)->toDateString(),
                'deadline'     => now()->addMonths(2)->toDateString(),
                'phases' => [
                    ['name' => 'Discovery & Strategy',  'order' => 1, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Requirements workshop', 'status' => 'completed'],
                        ['title' => 'Technical specification', 'status' => 'completed'],
                    ]],
                    ['name' => 'UX / Wireframes',       'order' => 2, 'status' => 'completed', 'tasks' => [
                        ['title' => 'User journey mapping', 'status' => 'completed'],
                        ['title' => 'Checkout flow wireframe', 'status' => 'completed'],
                    ]],
                    ['name' => 'Design',                 'order' => 3, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Design system / brand', 'status' => 'completed'],
                        ['title' => 'Product listing page design', 'status' => 'completed'],
                        ['title' => 'Cart & checkout design', 'status' => 'completed'],
                    ]],
                    ['name' => 'Development',            'order' => 4, 'status' => 'in_progress', 'tasks' => [
                        ['title' => 'WordPress + WooCommerce install', 'status' => 'completed'],
                        ['title' => 'Custom theme development', 'status' => 'in_progress'],
                        ['title' => 'B2B pricing plugin', 'status' => 'in_progress'],
                        ['title' => 'Trade account portal', 'status' => 'pending'],
                    ]],
                    ['name' => 'Product Import',         'order' => 5, 'status' => 'pending', 'tasks' => [
                        ['title' => 'CSV mapping from 3PL', 'status' => 'pending'],
                        ['title' => 'Bulk WooCommerce import', 'status' => 'pending'],
                        ['title' => 'Category structure', 'status' => 'pending'],
                    ]],
                    ['name' => 'Payment Integration',    'order' => 6, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Stripe integration', 'status' => 'pending'],
                        ['title' => 'BACS / trade account checkout', 'status' => 'pending'],
                    ]],
                    ['name' => 'Testing & QA',           'order' => 7, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Full basket & checkout test', 'status' => 'pending'],
                        ['title' => 'B2B pricing validation', 'status' => 'pending'],
                    ]],
                    ['name' => 'SEO & Analytics Setup',  'order' => 8, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Yoast SEO configuration', 'status' => 'pending'],
                        ['title' => 'GA4 + Search Console', 'status' => 'pending'],
                    ]],
                    ['name' => 'Launch',                 'order' => 9, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Pre-launch checklist', 'status' => 'pending'],
                        ['title' => 'DNS cutover', 'status' => 'pending'],
                    ]],
                ],
            ],
            // --- Active ---
            [
                'title'        => 'Oakfield Dental – Practice Website',
                'client_id'    => $oakfield?->id,
                'lead_id'      => $leadOakfield?->id,
                'assigned_to'  => $developer2?->id,
                'service_type' => 'wizytowka',
                'status'       => 'active',
                'description'  => 'Brochure site with NHS & private patient info, online booking via Doctify, and CQC compliance statement.',
                'budget'       => 2200.00,
                'currency'     => 'GBP',
                'start_date'   => now()->subWeeks(5)->toDateString(),
                'deadline'     => now()->addWeeks(3)->toDateString(),
                'phases' => [
                    ['name' => 'Discovery & Brief',   'order' => 1, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Gather all compliance docs', 'status' => 'completed'],
                        ['title' => 'Content brief sent', 'status' => 'completed'],
                    ]],
                    ['name' => 'Design Mockups',      'order' => 2, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Homepage design', 'status' => 'completed'],
                        ['title' => 'Treatment pages layout', 'status' => 'completed'],
                    ]],
                    ['name' => 'Development',         'order' => 3, 'status' => 'in_progress', 'tasks' => [
                        ['title' => 'Laravel + Filament CMS setup', 'status' => 'completed'],
                        ['title' => 'Build homepage', 'status' => 'completed'],
                        ['title' => 'Treatments section', 'status' => 'in_progress'],
                        ['title' => 'Booking widget integration', 'status' => 'pending'],
                    ]],
                    ['name' => 'Content Integration', 'order' => 4, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Awaiting content from client', 'status' => 'pending'],
                    ]],
                    ['name' => 'Testing & QA',        'order' => 5, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Accessibility checks (WCAG)', 'status' => 'pending'],
                        ['title' => 'Mobile review', 'status' => 'pending'],
                    ]],
                    ['name' => 'Launch & Handover',   'order' => 6, 'status' => 'pending', 'tasks' => [
                        ['title' => 'SSL + hosting', 'status' => 'pending'],
                        ['title' => 'Handover docs', 'status' => 'pending'],
                    ]],
                ],
            ],
            // --- On Hold ---
            [
                'title'        => 'Coastal Escapes – Static Brochure Site',
                'client_id'    => $coastal?->id,
                'lead_id'      => null,
                'assigned_to'  => $developer?->id,
                'service_type' => 'wizytowka',
                'status'       => 'on_hold',
                'description'  => 'Simple 5-page holiday rental brochure site. On hold pending client content delivery.',
                'budget'       => 1800.00,
                'currency'     => 'GBP',
                'start_date'   => now()->subMonths(3)->toDateString(),
                'deadline'     => now()->subMonths(1)->toDateString(),
                'phases' => [
                    ['name' => 'Discovery & Brief',   'order' => 1, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Brief received and confirmed', 'status' => 'completed'],
                    ]],
                    ['name' => 'Design Mockups',      'order' => 2, 'status' => 'completed', 'tasks' => [
                        ['title' => 'Homepage design approved', 'status' => 'completed'],
                    ]],
                    ['name' => 'Development',         'order' => 3, 'status' => 'on_hold', 'tasks' => [
                        ['title' => 'Build homepage', 'status' => 'completed'],
                        ['title' => 'Properties listing page – BLOCKED', 'status' => 'on_hold'],
                    ]],
                    ['name' => 'Content Integration', 'order' => 4, 'status' => 'pending', 'tasks' => [
                        ['title' => 'Waiting on client photos + copy', 'status' => 'pending'],
                    ]],
                    ['name' => 'Testing & QA',        'order' => 5, 'status' => 'pending', 'tasks' => []],
                    ['name' => 'Launch & Handover',   'order' => 6, 'status' => 'pending', 'tasks' => []],
                ],
            ],
        ];

        foreach ($projects as $projectData) {
            $phases = $projectData['phases'];
            unset($projectData['phases']);

            // Map project status (on_hold is valid for projects)
            $project = Project::firstOrCreate(
                ['title' => $projectData['title'], 'client_id' => $projectData['client_id']],
                $projectData
            );

            // Phase status enum: pending | active | completed
            $phaseStatusMap = ['in_progress' => 'active', 'on_hold' => 'pending'];
            // Task status enum: todo | in_progress | review | done
            $taskStatusMap  = ['completed' => 'done', 'pending' => 'todo', 'on_hold' => 'todo'];

            foreach ($phases as $phaseData) {
                $tasks = $phaseData['tasks'] ?? [];
                unset($phaseData['tasks']);
                $phaseData['project_id'] = $project->id;
                $phaseData['status']     = $phaseStatusMap[$phaseData['status']] ?? $phaseData['status'];

                $phase = ProjectPhase::firstOrCreate(
                    ['project_id' => $project->id, 'name' => $phaseData['name']],
                    $phaseData
                );

                foreach ($tasks as $taskData) {
                    $taskData['project_id'] = $project->id;
                    $taskData['phase_id']   = $phase->id;
                    $taskData['status']     = $taskStatusMap[$taskData['status']] ?? $taskData['status'];
                    $taskData['assigned_to'] = $projectData['assigned_to'] ?? null;

                    ProjectTask::firstOrCreate(
                        ['project_id' => $project->id, 'phase_id' => $phase->id, 'title' => $taskData['title']],
                        $taskData
                    );
                }
            }
        }
    }
}
