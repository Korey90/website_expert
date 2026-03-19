<?php

namespace Database\Seeders;

use App\Models\CalculatorPricing;
use Illuminate\Database\Seeder;

class CalculatorPricingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // -------------------------------------------------------
            // Project Types
            // -------------------------------------------------------
            [
                'category'     => 'project_type',
                'key'          => 'business_card',
                'label'        => 'Business Card Website',
                'description'  => 'Up to 5 pages. Perfect for sole traders and small businesses.',
                'base_cost'    => 799.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'category'     => 'project_type',
                'key'          => 'brochure',
                'label'        => 'Brochure Website',
                'description'  => '6–15 pages. Ideal for established businesses wanting a professional presence.',
                'base_cost'    => 1499.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
            [
                'category'     => 'project_type',
                'key'          => 'landing_page',
                'label'        => 'Landing Page / Campaign Page',
                'description'  => 'Single high-conversion page for ads or launches.',
                'base_cost'    => 599.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 3,
                'is_active'    => true,
            ],
            [
                'category'     => 'project_type',
                'key'          => 'ecommerce',
                'label'        => 'E-Commerce Store',
                'description'  => 'Full online shop. WooCommerce or headless. Includes up to 500 products.',
                'base_cost'    => 2999.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 4,
                'is_active'    => true,
            ],
            [
                'category'     => 'project_type',
                'key'          => 'web_app',
                'label'        => 'Custom Web Application',
                'description'  => 'Bespoke Laravel / React application. Custom quote — contact us.',
                'base_cost'    => 5999.00,
                'monthly_cost' => 0.00,
                'cost_formula' => 'base + (features_count * 800)',
                'currency'     => 'GBP',
                'sort_order'   => 5,
                'is_active'    => true,
            ],
            // -------------------------------------------------------
            // Hosting Plans
            // -------------------------------------------------------
            [
                'category'     => 'hosting',
                'key'          => 'shared_hosting',
                'label'        => 'Shared Hosting',
                'description'  => 'Standard shared hosting. Suitable for brochure sites with low traffic.',
                'base_cost'    => 0.00,
                'monthly_cost' => 9.99,
                'currency'     => 'GBP',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'category'     => 'hosting',
                'key'          => 'managed_wp',
                'label'        => 'Managed WordPress Hosting',
                'description'  => 'Optimised WP hosting with daily backups, staging, and CDN.',
                'base_cost'    => 0.00,
                'monthly_cost' => 29.99,
                'currency'     => 'GBP',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
            [
                'category'     => 'hosting',
                'key'          => 'vps',
                'label'        => 'VPS / Cloud Server',
                'description'  => 'Dedicated VPS with root access. Ideal for web apps and large e-commerce.',
                'base_cost'    => 0.00,
                'monthly_cost' => 79.99,
                'currency'     => 'GBP',
                'sort_order'   => 3,
                'is_active'    => true,
            ],
            // -------------------------------------------------------
            // CMS Options
            // -------------------------------------------------------
            [
                'category'     => 'cms',
                'key'          => 'no_cms',
                'label'        => 'No CMS (Static)',
                'description'  => 'Plain HTML/CSS/JS. No ongoing content editing capability.',
                'base_cost'    => 0.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'category'     => 'cms',
                'key'          => 'wordpress',
                'label'        => 'WordPress CMS',
                'description'  => 'Full WordPress CMS. Easily manage pages, blog, and media.',
                'base_cost'    => 299.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
            [
                'category'     => 'cms',
                'key'          => 'laravel_filament',
                'label'        => 'Custom Laravel + Filament CMS',
                'description'  => 'Bespoke admin panel built with Laravel & Filament. More powerful than WordPress.',
                'base_cost'    => 799.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 3,
                'is_active'    => true,
            ],
            // -------------------------------------------------------
            // Add-ons
            // -------------------------------------------------------
            [
                'category'     => 'addon',
                'key'          => 'seo_foundation',
                'label'        => 'SEO Foundation Package',
                'description'  => 'On-page SEO, meta tags, schema markup, XML sitemap, and GSC setup.',
                'base_cost'    => 499.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 1,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'seo_retainer',
                'label'        => 'Monthly SEO Retainer',
                'description'  => 'Ongoing keyword research, content, link building, and monthly reporting.',
                'base_cost'    => 0.00,
                'monthly_cost' => 599.00,
                'currency'     => 'GBP',
                'sort_order'   => 2,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'google_ads',
                'label'        => 'Google Ads Management',
                'description'  => 'PPC campaign setup and monthly management. Ad spend billed separately.',
                'base_cost'    => 299.00,
                'monthly_cost' => 399.00,
                'currency'     => 'GBP',
                'sort_order'   => 3,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'maintenance',
                'label'        => 'Website Maintenance Retainer',
                'description'  => 'Monthly updates, security patches, content edits (up to 2 hours), and uptime monitoring.',
                'base_cost'    => 0.00,
                'monthly_cost' => 149.00,
                'currency'     => 'GBP',
                'sort_order'   => 4,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'live_chat',
                'label'        => 'Live Chat Integration',
                'description'  => 'Tawk.to or Crisp live chat installation and configuration.',
                'base_cost'    => 149.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 5,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'booking_system',
                'label'        => 'Appointment Booking System',
                'description'  => 'Online booking with calendar sync (Calendly / custom). Ideal for clinics, salons, trades.',
                'base_cost'    => 399.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 6,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'multilingual',
                'label'        => 'Multilingual Website',
                'description'  => 'WPML or custom i18n setup. Price per additional language.',
                'base_cost'    => 350.00,
                'monthly_cost' => 0.00,
                'cost_formula' => 'base * language_count',
                'currency'     => 'GBP',
                'sort_order'   => 7,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'copywriting',
                'label'        => 'Professional Copywriting',
                'description'  => 'SEO-optimised website copy written by our UK copywriter. Per page.',
                'base_cost'    => 120.00,
                'monthly_cost' => 0.00,
                'cost_formula' => 'base * page_count',
                'currency'     => 'GBP',
                'sort_order'   => 8,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'photography',
                'label'        => 'Professional Photography',
                'description'  => 'Half-day shoot at your premises. Approx 30–50 edited images.',
                'base_cost'    => 599.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 9,
                'is_active'    => true,
            ],
            [
                'category'     => 'addon',
                'key'          => 'logo_design',
                'label'        => 'Logo & Brand Identity',
                'description'  => '3 initial concepts, 2 rounds of revisions. Final files in all formats.',
                'base_cost'    => 449.00,
                'monthly_cost' => 0.00,
                'currency'     => 'GBP',
                'sort_order'   => 10,
                'is_active'    => true,
            ],
        ];

        foreach ($items as $data) {
            CalculatorPricing::firstOrCreate(
                ['category' => $data['category'], 'key' => $data['key']],
                $data
            );
        }
    }
}
