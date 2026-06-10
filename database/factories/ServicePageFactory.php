<?php

namespace Database\Factories;

use App\Models\ServicePage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServicePage>
 */
class ServicePageFactory extends Factory
{
    protected $model = ServicePage::class;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        return [
            'slug'             => Str::slug($title) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'title'            => ['pl' => $title, 'en' => '', 'pt' => ''],
            'meta_title'       => ['pl' => $title, 'en' => '', 'pt' => ''],
            'meta_description' => ['pl' => $this->faker->sentence(), 'en' => '', 'pt' => ''],
            'nav_label'        => ['pl' => $title, 'en' => '', 'pt' => ''],
            'is_published'     => true,
            'show_in_nav'      => false,
            'sort_order'       => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function withFullTranslations(): static
    {
        return $this->state(function (array $attributes) {
            $pl = $attributes['title']['pl'] ?? 'Title';

            return [
                'title'            => ['pl' => $pl, 'en' => "EN: {$pl}", 'pt' => "PT: {$pl}"],
                'meta_title'       => ['pl' => $pl, 'en' => "EN: {$pl}", 'pt' => "PT: {$pl}"],
                'meta_description' => ['pl' => 'Opis PL', 'en' => 'EN Description', 'pt' => 'PT Descrição'],
                'nav_label'        => ['pl' => $pl, 'en' => "EN: {$pl}", 'pt' => "PT: {$pl}"],
            ];
        });
    }
}
