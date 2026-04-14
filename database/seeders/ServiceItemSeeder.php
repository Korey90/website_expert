<?php

namespace Database\Seeders;

use App\Models\ServiceItem;
use Illuminate\Database\Seeder;

class ServiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title'       => ['en' => 'Brochure Websites',        'pl' => 'Strony wizytówkowe',            'pt' => 'Sites Institucionais'],
                'description' => [
                    'en' => 'Professional, mobile-first websites that create the right first impression and generate enquiries.',
                    'pl' => 'Profesjonalne strony firmowe mobile-first, które budują zaufanie i generują zapytania.',
                    'pt' => 'Websites profissionais mobile-first que criam a primeira impressão certa e geram pedidos de contacto.',
                ],
                'icon'       => 'monitor',
                'price_from' => '£799',
                'link'       => '/services/brochure-websites',
                'slug'       => 'brochure-websites',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'title'       => ['en' => 'E-Commerce Stores',         'pl' => 'Sklepy e-commerce',              'pt' => 'Lojas E-Commerce'],
                'description' => [
                    'en' => 'Sell online with confidence. WooCommerce and headless solutions tailored to your products.',
                    'pl' => 'Szybkie, bezpieczne sklepy online z integracją płatności i systemu zamówień.',
                    'pt' => 'Venda online com confiança. Soluções WooCommerce e headless adaptadas aos seus produtos.',
                ],
                'icon'       => 'shopping-cart',
                'price_from' => '£2,999',
                'link'       => '/services/ecommerce',
                'slug'       => 'ecommerce',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'title'       => ['en' => 'Web Applications',          'pl' => 'Aplikacje internetowe',         'pt' => 'Aplicações Web'],
                'description' => [
                    'en' => 'Bespoke Laravel and React applications. Customer portals, SaaS platforms, booking systems.',
                    'pl' => 'Dedykowane aplikacje Laravel i React. Portale klientów, platformy SaaS, systemy rezerwacji.',
                    'pt' => 'Aplicações Laravel e React à medida. Portais de clientes, plataformas SaaS, sistemas de reservas.',
                ],
                'icon'       => 'code',
                'price_from' => '£5,999',
                'link'       => '/services/web-applications',
                'slug'       => 'web-applications',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
            [
                'title'       => ['en' => 'SEO & Digital Marketing',   'pl' => 'SEO i Marketing Cyfrowy',       'pt' => 'SEO e Marketing Digital'],
                'description' => [
                    'en' => 'Rank higher, attract more visitors, and convert them into paying customers.',
                    'pl' => 'Zaistniej w Google. Audyty SEO, optymalizacja on-page i strategia contentu.',
                    'pt' => 'Posicione-se mais alto, atraia mais visitantes e converta-os em clientes pagantes.',
                ],
                'icon'       => 'search',
                'price_from' => '£499',
                'link'       => '/services/seo',
                'slug'       => 'seo',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 4,
            ],
            [
                'title'       => ['en' => 'Google Ads (PPC)',           'pl' => 'Google Ads (PPC)',               'pt' => 'Google Ads (PPC)'],
                'description' => [
                    'en' => 'Targeted pay-per-click campaigns that deliver measurable ROI from day one.',
                    'pl' => 'Kampanie płatne z realnym ROI – konfiguracja, optymalizacja i raportowanie.',
                    'pt' => 'Campanhas pagas por clique com ROI mensurável desde o primeiro dia.',
                ],
                'icon'       => 'bar-chart',
                'price_from' => '£399/mo',
                'link'       => '/services/google-ads',
                'slug'       => 'google-ads',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 5,
            ],
            [
                'title'       => ['en' => 'Meta / Pixel Ads',           'pl' => 'Reklamy Meta / Pixel',          'pt' => 'Anúncios Meta / Pixel'],
                'description' => [
                    'en' => 'Facebook & Instagram campaigns with Pixel tracking, retargeting and lookalike audiences.',
                    'pl' => 'Kampanie Facebook & Instagram z Pixelem, remarketingiem i grupami lookalike.',
                    'pt' => 'Campanhas no Facebook & Instagram com rastreamento Pixel, remarketing e públicos semelhantes.',
                ],
                'icon'       => 'zap',
                'price_from' => '£349/mo',
                'link'       => '/services/meta-ads',
                'slug'       => 'meta-ads',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 6,
            ],
            [
                'title'       => ['en' => 'Content Creation',           'pl' => 'Tworzenie treści',               'pt' => 'Criação de Conteúdo'],
                'description' => [
                    'en' => 'Copywriting, blog articles, product descriptions and social media content in EN, PL and PT.',
                    'pl' => 'Copywriting, artykuły blogowe, opisy produktów i treści social media w PL, EN i PT.',
                    'pt' => 'Copywriting, artigos de blog, descrições de produtos e conteúdo para redes sociais em PT, EN e PL.',
                ],
                'icon'       => 'file-text',
                'price_from' => '£199/mo',
                'link'       => '/services/content',
                'slug'       => 'content',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 7,
            ],
            [
                'title'       => ['en' => 'Security & Performance Audits', 'pl' => 'Audyty bezpieczeństwa i wydajności', 'pt' => 'Auditorias de Segurança e Desempenho'],
                'description' => [
                    'en' => 'In-depth security testing, Core Web Vitals optimisation and actionable performance reports.',
                    'pl' => 'Głęboka analiza bezpieczeństwa, optymalizacja Core Web Vitals i raporty z rekomendacjami.',
                    'pt' => 'Testes de segurança aprofundados, otimização de Core Web Vitals e relatórios de desempenho.',
                ],
                'icon'       => 'shield',
                'price_from' => '£299',
                'link'       => '/services/audits',
                'slug'       => 'audits',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 8,
            ],
            [
                'title'       => ['en' => 'Website Maintenance',        'pl' => 'Opieka nad stroną',              'pt' => 'Manutenção de Website'],
                'description' => [
                    'en' => 'Keep your site fast, secure, and up to date with our monthly care plans.',
                    'pl' => 'Szybki hosting z SSL, backupami i opieką techniczną w pakiecie.',
                    'pt' => 'Manter o seu site rápido, seguro e atualizado com os nossos planos de manutenção mensais.',
                ],
                'icon'       => 'settings',
                'price_from' => '£149/mo',
                'link'       => '/services/maintenance',
                'slug'       => 'maintenance',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 9,
            ],
        ];

        foreach ($items as $data) {
            ServiceItem::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
