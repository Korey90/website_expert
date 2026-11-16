<?php

namespace Tests\Feature\Filament;

use Illuminate\Support\Arr;
use Tests\TestCase;

class AdminLocalizationContractTest extends TestCase
{
    public function test_polish_and_portuguese_catalogs_match_the_english_contract(): void
    {
        $englishFiles = collect(glob(lang_path('en/*.php')))
            ->map(fn (string $path): string => basename($path))
            ->sort()
            ->values();

        $this->assertNotEmpty($englishFiles);

        foreach ($englishFiles as $file) {
            $englishKeys = array_keys(Arr::dot(require lang_path("en/{$file}")));
            sort($englishKeys);

            foreach (['pl', 'pt'] as $locale) {
                $translatedPath = lang_path("{$locale}/{$file}");

                $this->assertFileExists($translatedPath);
                $translatedKeys = array_keys(Arr::dot(require $translatedPath));
                sort($translatedKeys);

                $this->assertSame(
                    $englishKeys,
                    $translatedKeys,
                    "Translation catalog [{$locale}/{$file}] does not match [en/{$file}].",
                );
            }
        }
    }
}
