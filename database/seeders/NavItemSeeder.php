<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\SiteSection;
use Illuminate\Database\Seeder;

class NavItemSeeder extends Seeder
{
    /** Sections that are decorative / not direct nav targets */
    private const SKIP = ['hero', 'footer', 'testimonials', 'cta_banner'];

    /**
     * DOM id may differ from section key.
     * Map section key → href hash when they differ.
     */
    private const HREF_MAP = [
        'cost_calculator' => '#calculate',
        'trust_strip'     => '#zaufali',
    ];

    /** Default labels per section key (fallback when SiteSection has no label) */
    private const LABELS = [
        'about'           => ['pl' => 'O nas',       'en' => 'About Us',        'pt' => 'Sobre Nós'],
        'trust_strip'     => ['pl' => 'Zaufali nam',  'en' => 'Trusted By',      'pt' => 'Confiança'],
        'services'        => ['pl' => 'Oferta',     'en' => 'Services',        'pt' => 'Serviços'],
        'saas_landing'    => ['pl' => 'SaaS',       'en' => 'SaaS',            'pt' => 'SaaS'],
        'process'         => ['pl' => 'Jak działamy','en' => 'How We Work',    'pt' => 'Como Trabalhamos'],
        'portfolio'       => ['pl' => 'Portfolio',  'en' => 'Portfolio',       'pt' => 'Portfolio'],
        'cost_calculator' => ['pl' => 'Kalkulator', 'en' => 'Cost Calculator', 'pt' => 'Calculadora'],
        'faq'             => ['pl' => 'FAQ',         'en' => 'FAQ',             'pt' => 'FAQ'],
        'contact'         => ['pl' => 'Kontakt',    'en' => 'Contact',         'pt' => 'Contacto'],
    ];

    public function run(): void
    {
        NavItem::truncate();

        SiteSection::whereNotIn('key', self::SKIP)
            ->orderBy('sort_order')
            ->each(function (SiteSection $section, int $index) {
                $key  = $section->key;
                $href = self::HREF_MAP[$key] ?? '#' . $key;

                $label = self::LABELS[$key]
                    ?? ['pl' => $section->label ?? $key, 'en' => $section->label ?? $key, 'pt' => $section->label ?? $key];

                NavItem::create([
                    'label'           => $label,
                    'href'            => $href,
                    'section_key'     => $key,
                    'sort_order'      => ($index + 1) * 10,
                    'is_active'       => true,
                    'open_in_new_tab' => false,
                ]);
            });
    }
}
