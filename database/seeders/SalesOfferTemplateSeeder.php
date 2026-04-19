<?php

namespace Database\Seeders;

use App\Models\SalesOfferTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SalesOfferTemplateSeeder extends Seeder
{
    private array $services = [
        'brochure-websites' => ['en' => 'Brochure Website',         'pl' => 'Strona Wizytówkowa'],
        'ecommerce'         => ['en' => 'E-Commerce',               'pl' => 'Sklep Internetowy'],
        'web-applications'  => ['en' => 'Web Application',          'pl' => 'Aplikacja Webowa'],
        'seo'               => ['en' => 'SEO & Digital Marketing',  'pl' => 'SEO i Marketing Cyfrowy'],
        'google-ads'        => ['en' => 'Google Ads (PPC)',         'pl' => 'Google Ads (PPC)'],
        'meta-ads'          => ['en' => 'Meta / Pixel Ads',         'pl' => 'Meta Ads'],
        'content'           => ['en' => 'Content Creation',         'pl' => 'Tworzenie Treści'],
        'audits'            => ['en' => 'Security & Performance Audits', 'pl' => 'Audyty Bezpieczeństwa i Wydajności'],
        'maintenance'       => ['en' => 'Website Maintenance',      'pl' => 'Opieka nad Stroną'],
    ];

    public function run(): void
    {
        if (SalesOfferTemplate::whereNull('business_id')->exists()) {
            $this->command->info('SalesOfferTemplate: global templates already seeded — skipping.');
            return;
        }

        $created = 0;

        foreach ($this->services as $slug => $names) {
            foreach (['pl', 'en'] as $lang) {
                $body = $this->loadBody($slug, $lang);

                SalesOfferTemplate::create([
                    'business_id'  => null,
                    'service_slug' => $slug,
                    'language'     => $lang,
                    'title'        => $names[$lang] . ' — ' . ($lang === 'pl' ? 'Oferta Sprzedażowa' : 'Sales Offer'),
                    'description'  => ($lang === 'pl' ? 'Globalny szablon oferty dla: ' : 'Global offer template for: ') . $names[$lang],
                    'body'         => $body,
                    'is_active'    => true,
                    'sort_order'   => 0,
                ]);

                $created++;
            }
        }

        $this->command->info("SalesOfferTemplate: {$created} global templates seeded.");
    }

    private function loadBody(string $slug, string $lang): string
    {
        $path = base_path("docs/sales/template-{$slug}-sales-offer-{$lang}.md");

        if (File::exists($path)) {
            return File::get($path);
        }

        $this->command->warn("SalesOfferTemplate: file not found: docs/sales/template-{$slug}-sales-offer-{$lang}.md — using empty body.");

        return '';
    }
}
