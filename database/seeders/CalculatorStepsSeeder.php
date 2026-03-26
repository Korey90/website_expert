<?php

namespace Database\Seeders;

use App\Models\CalculatorStep;
use Illuminate\Database\Seeder;

class CalculatorStepsSeeder extends Seeder
{
    public function run(): void
    {
        CalculatorStep::truncate();

        $steps = [
            [
                'step_number' => 1,
                'sort_order'  => 1,
                'is_active'   => true,
                'question_en' => 'What type of project do you need?',
                'question_pl' => 'Jakiego projektu potrzebujesz?',
                'question_pt' => 'Que tipo de projeto precisa?',
                'hint_en'     => 'Choose the project type that best describes your needs.',
                'hint_pl'     => 'Wybierz typ projektu, który najlepiej opisuje Twoje potrzeby.',
                'hint_pt'     => 'Escolha o tipo de projeto que melhor descreve as suas necessidades.',
            ],
            [
                'step_number' => 2,
                'sort_order'  => 2,
                'is_active'   => true,
                'question_en' => 'How many pages do you need?',
                'question_pl' => 'Ile podstron?',
                'question_pt' => 'Quantas páginas precisa?',
                'hint_en'     => 'E.g. Home, About, Services, Blog, Contact.',
                'hint_pl'     => 'Np. Strona główna, O nas, Usługi, Blog, Kontakt.',
                'hint_pt'     => 'Ex.: Início, Sobre, Serviços, Blog, Contacto.',
            ],
            [
                'step_number' => 3,
                'sort_order'  => 3,
                'is_active'   => true,
                'question_en' => 'What level of design do you need?',
                'question_pl' => 'Jaki poziom designu?',
                'question_pt' => 'Que nível de design precisa?',
                'hint_en'     => 'Custom design means a unique graphic project from scratch.',
                'hint_pl'     => 'Custom design to indywidualny projekt graficzny od zera.',
                'hint_pt'     => 'Design personalizado significa um projeto gráfico único de raiz.',
            ],
            [
                'step_number' => 4,
                'sort_order'  => 4,
                'is_active'   => true,
                'question_en' => 'Do you need a content management system?',
                'question_pl' => 'Czy potrzebujesz systemu CMS?',
                'question_pt' => 'Precisa de um sistema de gestão de conteúdo?',
                'hint_en'     => 'A CMS lets you edit content without a developer.',
                'hint_pl'     => 'CMS pozwala samodzielnie edytować treści bez programisty.',
                'hint_pt'     => 'Um CMS permite editar conteúdo sem precisar de um programador.',
            ],
            [
                'step_number' => 5,
                'sort_order'  => 5,
                'is_active'   => true,
                'question_en' => 'Which integrations do you need?',
                'question_pl' => 'Jakie integracje?',
                'question_pt' => 'Que integrações precisa?',
                'hint_en'     => 'You can choose multiple options or skip this step.',
                'hint_pl'     => 'Możesz wybrać wiele opcji lub pominąć ten krok.',
                'hint_pt'     => 'Pode escolher várias opções ou ignorar este passo.',
            ],
            [
                'step_number' => 6,
                'sort_order'  => 6,
                'is_active'   => true,
                'question_en' => 'Do you want an SEO package?',
                'question_pl' => 'Pakiet SEO?',
                'question_pt' => 'Pretende um pacote SEO?',
                'hint_en'     => 'Search engine optimisation — more organic traffic.',
                'hint_pl'     => 'Optymalizacja pod wyszukiwarki – więcej ruchu organicznego.',
                'hint_pt'     => 'Otimização para motores de pesquisa — mais tráfego orgânico.',
            ],
            [
                'step_number' => 7,
                'sort_order'  => 7,
                'is_active'   => true,
                'question_en' => 'When do you need the project?',
                'question_pl' => 'Kiedy potrzebujesz projektu?',
                'question_pt' => 'Quando precisa do projeto?',
                'hint_en'     => 'Faster timelines require additional resources.',
                'hint_pl'     => 'Szybsze terminy wymagają dodatkowych zasobów.',
                'hint_pt'     => 'Prazos mais curtos requerem recursos adicionais.',
            ],
            [
                'step_number' => 8,
                'sort_order'  => 8,
                'is_active'   => true,
                'question_en' => 'Hosting & maintenance?',
                'question_pl' => 'Hosting i utrzymanie?',
                'question_pt' => 'Alojamento e manutenção?',
                'hint_en'     => 'Hosting prices shown as an annual add-on.',
                'hint_pl'     => 'Ceny hostingu podane jako dopłata roczna.',
                'hint_pt'     => 'Preços de alojamento apresentados como add-on anual.',
            ],
        ];

        foreach ($steps as $data) {
            CalculatorStep::create($data);
        }
    }
}
