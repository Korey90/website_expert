<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::where('email', 'admin@websiteexpert.co.uk')->first();
        $manager = User::where('email', 'manager@websiteexpert.co.uk')->first();

        $hargreaves = Client::where('company_name', 'Hargreaves & Sons Solicitors')->first();
        $nts        = Client::where('company_name', 'Northern Trade Supplies Ltd')->first();
        $oakfield   = Client::where('company_name', 'Oakfield Dental Practice')->first();
        $pinnacle   = Client::where('company_name', 'Pinnacle Recruitment Group')->first();
        $bloom      = Client::where('company_name', 'Bloom & Grow Garden Centre')->first();

        $leadNts     = Lead::where('title', 'E-Commerce Platform – Northern Trade Supplies')->first();
        $leadPinnacle = Lead::where('title', 'Custom Job Board App – Pinnacle Recruitment')->first();
        $leadBloom   = Lead::where('title', 'Garden Centre Online Shop – Bloom & Grow')->first();
        $leadHargreaves = Lead::where('title', 'SEO Audit & Retainer – Hargreaves Solicitors')->first();

        $quotes = [
            // Accepted — NTS E-Commerce
            [
                'number'          => 'QUO-2024-001',
                'client_id'       => $nts?->id,
                'lead_id'         => $leadNts?->id,
                'created_by'      => $manager?->id,
                'status'          => 'accepted',
                'currency'        => 'GBP',
                'discount_amount' => 500.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Quote for full WooCommerce e-commerce platform including B2B trade portal.',
                'terms'           => '50% deposit required before work commences. Remaining balance due on completion. Prices exclude VAT.',
                'valid_until'     => now()->subMonths(5)->toDateString(),
                'sent_at'         => now()->subMonths(6),
                'accepted_at'     => now()->subMonths(6)->addDays(3),
                'items' => [
                    ['description' => 'WooCommerce Platform Setup & Configuration', 'quantity' => 1, 'unit_price' => 1200.00, 'order' => 1],
                    ['description' => 'Custom Theme Design & Development',           'quantity' => 1, 'unit_price' => 3500.00, 'order' => 2],
                    ['description' => 'B2B Trade Account Portal',                    'quantity' => 1, 'unit_price' => 2800.00, 'order' => 3],
                    ['description' => 'Product Import (up to 3,500 SKUs)',           'quantity' => 1, 'unit_price' => 1800.00, 'order' => 4],
                    ['description' => 'Stripe Payment Integration',                  'quantity' => 1, 'unit_price' => 600.00,  'order' => 5],
                    ['description' => 'SEO Foundation & Analytics Setup',            'quantity' => 1, 'unit_price' => 800.00,  'order' => 6],
                    ['description' => '12 Months Managed WordPress Hosting',        'quantity' => 1, 'unit_price' => 800.00,  'order' => 7],
                ],
            ],
            // Accepted — Oakfield Dental
            [
                'number'          => 'QUO-2024-002',
                'client_id'       => $oakfield?->id,
                'lead_id'         => null,
                'created_by'      => $manager?->id,
                'status'          => 'accepted',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Brochure website for dental practice with online booking and patient information.',
                'terms'           => '50% on project start, 50% on go-live. Quote valid for 30 days.',
                'valid_until'     => now()->subMonths(2)->toDateString(),
                'sent_at'         => now()->subMonths(3),
                'accepted_at'     => now()->subMonths(3)->addDays(7),
                'items' => [
                    ['description' => 'Website Design (8 pages)',           'quantity' => 1, 'unit_price' => 900.00,  'order' => 1],
                    ['description' => 'Laravel CMS Development',            'quantity' => 1, 'unit_price' => 800.00,  'order' => 2],
                    ['description' => 'Doctify Booking Widget Integration', 'quantity' => 1, 'unit_price' => 350.00,  'order' => 3],
                    ['description' => '12 Months SSL & Hosting',            'quantity' => 1, 'unit_price' => 150.00,  'order' => 4],
                ],
            ],
            // Sent — Pinnacle (awaiting response)
            [
                'number'          => 'QUO-2025-001',
                'client_id'       => $pinnacle?->id,
                'lead_id'         => $leadPinnacle?->id,
                'created_by'      => $manager?->id,
                'status'          => 'sent',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Custom job board with CV upload, employer portal, and applicant status tracking.',
                'terms'           => 'Payment terms: 40% on project start, 40% at beta sign-off, 20% on launch.',
                'valid_until'     => now()->addDays(14)->toDateString(),
                'sent_at'         => now()->subDays(7),
                'items' => [
                    ['description' => 'Job Board Architecture & Database Design',    'quantity' => 1, 'unit_price' => 800.00,  'order' => 1],
                    ['description' => 'Candidate Portal (register, CV upload, apply)','quantity' => 1, 'unit_price' => 1800.00, 'order' => 2],
                    ['description' => 'Employer Portal (post jobs, manage applicants)','quantity' => 1, 'unit_price' => 2200.00, 'order' => 3],
                    ['description' => 'Admin Dashboard (approve listings, analytics)', 'quantity' => 1, 'unit_price' => 900.00,  'order' => 4],
                    ['description' => 'Email Notifications & Alerts',                 'quantity' => 1, 'unit_price' => 400.00,  'order' => 5],
                    ['description' => '12 Months Hosting & Maintenance',              'quantity' => 1, 'unit_price' => 600.00,  'order' => 6],
                ],
            ],
            // Sent — Bloom Garden Centre
            [
                'number'          => 'QUO-2025-002',
                'client_id'       => $bloom?->id,
                'lead_id'         => $leadBloom?->id,
                'created_by'      => $manager?->id,
                'status'          => 'sent',
                'currency'        => 'GBP',
                'discount_amount' => 200.00,
                'vat_rate'        => 20.00,
                'notes'           => 'WooCommerce shop with click & collect and seasonal promotion features. Loyalty discount applied.',
                'terms'           => '50% deposit required. Remaining 50% due on delivery.',
                'valid_until'     => now()->addDays(21)->toDateString(),
                'sent_at'         => now()->subDays(3),
                'items' => [
                    ['description' => 'WooCommerce Setup & Configuration',   'quantity' => 1, 'unit_price' => 800.00,  'order' => 1],
                    ['description' => 'Custom Theme (mobile-first design)',   'quantity' => 1, 'unit_price' => 1400.00, 'order' => 2],
                    ['description' => 'Click & Collect Plugin',               'quantity' => 1, 'unit_price' => 400.00,  'order' => 3],
                    ['description' => 'Seasonal Promotions & Discount Engine','quantity' => 1, 'unit_price' => 600.00,  'order' => 4],
                    ['description' => '12 Months Hosting (WP Managed)',       'quantity' => 1, 'unit_price' => 480.00,  'order' => 5],
                ],
            ],
            // Draft — SEO Retainer for Hargreaves
            [
                'number'          => 'QUO-2025-003',
                'client_id'       => $hargreaves?->id,
                'lead_id'         => $leadHargreaves?->id,
                'created_by'      => $admin?->id,
                'status'          => 'draft',
                'currency'        => 'GBP',
                'discount_amount' => 0.00,
                'vat_rate'        => 20.00,
                'notes'           => 'Draft SEO retainer proposal. 6-month contract with monthly reporting.',
                'terms'           => 'Monthly invoiced in advance. 1-month notice period after initial 6-month term.',
                'valid_until'     => now()->addDays(30)->toDateString(),
                'items' => [
                    ['description' => 'SEO Technical Audit & Action Plan (one-off)', 'quantity' => 1,  'unit_price' => 600.00, 'order' => 1],
                    ['description' => 'Monthly SEO Retainer (keyword research, content, links)', 'quantity' => 6, 'unit_price' => 600.00, 'order' => 2],
                    ['description' => 'Monthly Analytics & Search Console Reporting', 'quantity' => 6, 'unit_price' => 0.00,   'order' => 3],
                ],
            ],
        ];

        foreach ($quotes as $quoteData) {
            $items = $quoteData['items'];
            unset($quoteData['items']);

            $quote = Quote::firstOrCreate(
                ['number' => $quoteData['number']],
                array_merge($quoteData, ['subtotal' => 0, 'vat_amount' => 0, 'total' => 0])
            );

            foreach ($items as $itemData) {
                $itemData['quote_id'] = $quote->id;
                $itemData['amount']   = $itemData['quantity'] * $itemData['unit_price'];

                QuoteItem::firstOrCreate(
                    ['quote_id' => $quote->id, 'order' => $itemData['order']],
                    $itemData
                );
            }

            // Recalculate totals
            $subtotal = $quote->items()->sum('amount');
            $discount = $quoteData['discount_amount'] ?? 0;
            $vatRate  = $quoteData['vat_rate'] ?? 20;
            $vat      = round(($subtotal - $discount) * ($vatRate / 100), 2);
            $quote->update([
                'subtotal'   => $subtotal,
                'vat_amount' => $vat,
                'total'      => $subtotal - $discount + $vat,
            ]);
        }
    }
}
