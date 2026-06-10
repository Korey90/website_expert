<?php

namespace Database\Factories;

use App\Models\ServicePage;
use App\Models\ServicePageBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePageBlock>
 */
class ServicePageBlockFactory extends Factory
{
    protected $model = ServicePageBlock::class;

    public function definition(): array
    {
        return [
            'service_page_id' => ServicePage::factory(),
            'type'            => $this->faker->randomElement(array_keys(ServicePageBlock::TYPES)),
            'sort_order'      => $this->faker->numberBetween(0, 99),
            'content'         => [],
            'settings'        => [],
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function hero(string $titlePl = 'Nagłówek Hero'): static
    {
        return $this->state([
            'type'    => 'hero',
            'content' => [
                'heading_pl'       => $titlePl,
                'heading_en'       => 'Hero Heading',
                'heading_pt'       => 'Título Hero',
                'subheading_pl'    => 'Podtytuł',
                'subheading_en'    => 'Subtitle',
                'subheading_pt'    => 'Subtítulo',
                'cta_label_pl'     => 'Skontaktuj się',
                'cta_label_en'     => 'Contact us',
                'cta_label_pt'     => 'Contacte-nos',
                'cta_url'          => '/contact',
            ],
        ]);
    }

    public function faq(): static
    {
        return $this->state([
            'type'    => 'faq',
            'content' => [
                'heading_pl' => 'FAQ',
                'heading_en' => 'FAQ',
                'heading_pt' => 'FAQ',
                'items' => [
                    [
                        'question_pl' => 'Pytanie po polsku?',
                        'question_en' => 'Question in English?',
                        'question_pt' => 'Pergunta em português?',
                        'answer_pl'   => 'Odpowiedź po polsku.',
                        'answer_en'   => 'Answer in English.',
                        'answer_pt'   => 'Resposta em português.',
                    ],
                ],
            ],
        ]);
    }
}
