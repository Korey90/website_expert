<?php

namespace Database\Seeders;

use App\Models\PortfolioProject;
use Illuminate\Database\Seeder;

class PortfolioProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'title'       => ['en' => 'Solicitors Website Redesign', 'pl' => 'Redesign strony kancelarii prawnej', 'pt' => 'Redesign do Site de Advogados'],
                'tag'         => ['en' => 'Brochure Website', 'pl' => 'Strona wizytówkowa', 'pt' => 'Site Institucional'],
                'description' => [
                    'en' => 'WCAG AA-compliant redesign for a Manchester solicitors firm. New case studies section, live chat integration.',
                    'pl' => 'Redesign strony dla kancelarii z Manchesteru zgodny z WCAG AA. Sekcja case studies, integracja live chat.',
                    'pt' => 'Redesign compatível com WCAG AA para uma firma de advogados em Manchester. Nova secção de casos, integração de live chat.',
                ],
                'result' => [
                    'en' => '+40% contact form conversions',
                    'pl' => '+40% konwersji formularza kontaktowego',
                    'pt' => '+40% de conversões no formulário de contacto',
                ],
                'client_name' => 'Hargreaves Solicitors',
                'slug'        => 'hargreaves-solicitors',
                'image_path'  => '/images/portfolio/hargreaves-solicitors.svg',
                'link'        => '/portfolio/hargreaves-solicitors',
                'tags'        => ['Laravel', 'Responsive', 'WCAG AA'],
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'title'       => ['en' => 'B2B E-Commerce Platform', 'pl' => 'Platforma e-commerce B2B', 'pt' => 'Plataforma E-Commerce B2B'],
                'tag'         => ['en' => 'E-Commerce', 'pl' => 'E-Commerce', 'pt' => 'E-Commerce'],
                'description' => [
                    'en' => 'Full B2B trade portal with 3,500+ SKUs, tiered pricing, custom quote builder and ERP integration.',
                    'pl' => 'Portal handlowy B2B z 3 500+ produktami, cenami poziomowymi, konfiguratorem wyceny i integracją ERP.',
                    'pt' => 'Portal comercial B2B completo com mais de 3.500 SKUs, preços por escalões, construtor de propostas e integração ERP.',
                ],
                'result' => [
                    'en' => '£80k online sales in first month',
                    'pl' => '80 tys. £ sprzedaży online w pierwszym miesiącu',
                    'pt' => '£80 mil em vendas online no primeiro mês',
                ],
                'client_name' => 'NTS Direct',
                'slug'        => 'nts-direct',
                'image_path'  => '/images/portfolio/nts-direct.svg',
                'link'        => '/portfolio/nts-direct',
                'tags'        => ['WooCommerce', 'B2B Portal', '3,500 SKUs'],
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'title'       => ['en' => 'Dental Practice Website', 'pl' => 'Strona kliniki dentystycznej', 'pt' => 'Website de Clínica Dentária'],
                'tag'         => ['en' => 'Healthcare Website', 'pl' => 'Strona medyczna', 'pt' => 'Website de Saúde'],
                'description' => [
                    'en' => 'CQC-compliant dental practice website with online booking, patient portal and Google Reviews integration.',
                    'pl' => 'Strona gabinetu dentystycznego zgodna z CQC, rezerwacja online, portal pacjenta, integracja Google Reviews.',
                    'pt' => 'Website de clínica dentária compatível com CQC, com marcações online, portal de pacientes e integração Google Reviews.',
                ],
                'result' => [
                    'en' => '60% more new patient enquiries',
                    'pl' => '60% więcej zgłoszeń nowych pacjentów',
                    'pt' => '60% mais pedidos de novos pacientes',
                ],
                'client_name' => 'Oakfield Dental',
                'slug'        => 'oakfield-dental',
                'image_path'  => '/images/portfolio/oakfield-dental.svg',
                'link'        => '/portfolio/oakfield-dental',
                'tags'        => ['Brochure', 'Booking Integration', 'CQC'],
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => 3,
            ],
        ];

        foreach ($projects as $data) {
            PortfolioProject::updateOrCreate(
                ['client_name' => $data['client_name'], 'link' => $data['link']],
                $data
            );
        }
    }
}
