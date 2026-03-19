<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $manager   = User::where('email', 'manager@websiteexpert.co.uk')->first();
        $developer = User::where('email', 'developer@websiteexpert.co.uk')->first();

        $stages = PipelineStage::pluck('id', 'slug');

        $hargreaves  = Client::where('company_name', 'Hargreaves & Sons Solicitors')->first();
        $oakfield    = Client::where('company_name', 'Oakfield Dental Practice')->first();
        $nts         = Client::where('company_name', 'Northern Trade Supplies Ltd')->first();
        $bloom       = Client::where('company_name', 'Bloom & Grow Garden Centre')->first();
        $pinnacle    = Client::where('company_name', 'Pinnacle Recruitment Group')->first();
        $coastal     = Client::where('company_name', 'Coastal Escapes Holiday Rentals')->first();
        $techstart   = Client::where('company_name', 'TechStart Labs Ltd')->first();

        $leads = [
            // Won leads (converted to projects)
            [
                'title'               => 'Website Redesign – Hargreaves Solicitors',
                'client_id'           => $hargreaves?->id,
                'pipeline_stage_id'   => $stages['won'],
                'assigned_to'         => $manager?->id,
                'value'               => 3800.00,
                'currency'            => 'GBP',
                'source'              => 'referral',
                'notes'               => 'Full redesign of 12-page solicitors website. WordPress to Laravel migration.',
                'expected_close_date' => now()->subMonths(4)->toDateString(),
                'won_at'              => now()->subMonths(4),
            ],
            [
                'title'               => 'E-Commerce Platform – Northern Trade Supplies',
                'client_id'           => $nts?->id,
                'pipeline_stage_id'   => $stages['won'],
                'assigned_to'         => $manager?->id,
                'value'               => 12500.00,
                'currency'            => 'GBP',
                'source'              => 'cold_outreach',
                'notes'               => 'Full WooCommerce build with B2B pricing tiers and trade account portal.',
                'expected_close_date' => now()->subMonths(6)->toDateString(),
                'won_at'              => now()->subMonths(6),
            ],
            [
                'title'               => 'Dental Practice Website – Oakfield',
                'client_id'           => $oakfield?->id,
                'pipeline_stage_id'   => $stages['won'],
                'assigned_to'         => $developer?->id,
                'value'               => 2200.00,
                'currency'            => 'GBP',
                'source' => 'contact_form',
                'notes'               => 'Brochure site with online booking integration. NHS and Private.',
                'expected_close_date' => now()->subMonths(3)->toDateString(),
                'won_at'              => now()->subMonths(3),
            ],
            // Active / In-progress pipeline leads
            [
                'title'               => 'Custom Job Board App – Pinnacle Recruitment',
                'client_id'           => $pinnacle?->id,
                'pipeline_stage_id'   => $stages['negotiation'],
                'assigned_to'         => $manager?->id,
                'value'               => 6500.00,
                'currency'            => 'GBP',
                'source'              => 'referral',
                'notes'               => 'Bespoke job board with CV upload, employer portal, and applicant tracking.',
                'expected_close_date' => now()->addDays(14)->toDateString(),
            ],
            [
                'title'               => 'MVP Web App – TechStart Labs',
                'client_id'           => $techstart?->id,
                'pipeline_stage_id'   => $stages['proposal-sent'],
                'assigned_to'         => $manager?->id,
                'value'               => 18000.00,
                'currency'            => 'GBP',
                'source' => 'contact_form',
                'notes'               => 'React + Laravel SaaS MVP. Requires subscription billing, user roles, and dashboard analytics.',
                'expected_close_date' => now()->addDays(30)->toDateString(),
                'calculator_data'     => [
                    'project_type' => 'web_app',
                    'pages'        => 0,
                    'features'     => ['auth', 'payments', 'dashboard', 'api'],
                    'hosting'      => 'managed',
                ],
            ],
            [
                'title'               => 'Garden Centre Online Shop – Bloom & Grow',
                'client_id'           => $bloom?->id,
                'pipeline_stage_id'   => $stages['proposal-sent'],
                'assigned_to'         => $developer?->id,
                'value'               => 3800.00,
                'currency'            => 'GBP',
                'source'              => 'social_media',
                'notes'               => 'WooCommerce shop with click & collect and seasonal promotions.',
                'expected_close_date' => now()->addDays(21)->toDateString(),
            ],
            [
                'title'               => 'SEO Audit & Retainer – Hargreaves Solicitors',
                'client_id'           => $hargreaves?->id,
                'pipeline_stage_id'   => $stages['contacted'],
                'assigned_to'         => $manager?->id,
                'value'               => 3600.00,
                'currency'            => 'GBP',
                'source' => 'referral',
                'notes'               => 'Existing client — upsell 6-month SEO retainer at £600/month.',
                'expected_close_date' => now()->addDays(7)->toDateString(),
            ],
            [
                'title'               => 'Holiday Booking Portal – Coastal Escapes',
                'client_id'           => $coastal?->id,
                'pipeline_stage_id'   => $stages['new-lead'],
                'assigned_to'         => $manager?->id,
                'value'               => 4500.00,
                'currency'            => 'GBP',
                'source' => 'other',
                'notes'               => 'Upsell from static site to dynamic booking portal with Stripe integration.',
                'expected_close_date' => now()->addMonths(2)->toDateString(),
            ],
            // Lost lead
            [
                'title'               => 'Brochure Website – Dental (Lost)',
                'client_id'           => $oakfield?->id,
                'pipeline_stage_id'   => $stages['lost'],
                'assigned_to'         => $developer?->id,
                'value'               => 1500.00,
                'currency'            => 'GBP',
                'source' => 'contact_form',
                'notes'               => 'Budget was lower than expected. Client went with cheaper competitor.',
                'expected_close_date' => now()->subMonths(2)->toDateString(),
                'lost_at'             => now()->subMonths(2),
                'lost_reason'         => 'Budget constraints — chose cheaper provider at £799.',
            ],
        ];

        foreach ($leads as $data) {
            Lead::firstOrCreate(
                ['title' => $data['title'], 'client_id' => $data['client_id']],
                $data
            );
        }
    }
}
