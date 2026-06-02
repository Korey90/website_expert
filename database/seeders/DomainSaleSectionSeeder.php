<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Seeder;

class DomainSaleSectionSeeder extends Seeder
{
    public function run(): void
    {
        SiteSection::updateOrCreate(
            ['key' => 'domains_hero'],
            [
                'label'       => 'Domains – Hero Section',
                'title'       => [
                    'en' => 'Find Your Perfect Domain Name',
                    'pl' => 'Znajdź idealną domenę dla swojej firmy',
                    'pt' => 'Encontre o Domínio Perfeito para o Seu Negócio',
                ],
                'subtitle'    => [
                    'en' => 'Register your domain in seconds. Competitive prices, instant activation and full DNS management included.',
                    'pl' => 'Zarejestruj domenę w kilka sekund. Konkurencyjne ceny, natychmiastowa aktywacja i pełne zarządzanie DNS w zestawie.',
                    'pt' => 'Registe o seu domínio em segundos. Preços competitivos, ativação imediata e gestão de DNS incluída.',
                ],
                'body'        => null,
                'button_text' => null,
                'button_url'  => null,
                'image_path'  => null,
                'extra'       => [
                    'search_placeholder_en' => 'Type your domain name…',
                    'search_placeholder_pl' => 'Wpisz nazwę domeny…',
                    'search_placeholder_pt' => 'Escreva o nome do domínio…',
                    'search_button_en'      => 'Check Availability',
                    'search_button_pl'      => 'Sprawdź dostępność',
                    'search_button_pt'      => 'Verificar Disponibilidade',
                    'popular_tlds'          => ['.com', '.co.uk', '.pl', '.eu', '.io', '.net'],
                ],
                'is_active'  => true,
                'sort_order' => 100,
            ]
        );
    }
}
