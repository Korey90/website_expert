<?php

namespace App\Services\ServicePage;

use App\Exceptions\LandingPageGenerationException;
use App\Services\LandingPage\OpenAiLandingClient;

class ServicePageTranslationService
{
    public function __construct(
        private readonly OpenAiLandingClient $client,
    ) {}

    /**
     * Translate ServicePage translatable fields from PL to EN and PT.
     *
     * @param  array{title: string, meta_title: string, meta_description: string, nav_label: string}  $source
     * @return array{en: array, pt: array}
     *
     * @throws LandingPageGenerationException
     */
    public function translatePage(array $source): array
    {
        $systemPrompt = <<<'PROMPT'
You are a professional translator for a web agency website.
Translate the provided fields from Polish into English (en) and Portuguese (pt).
Return ONLY a valid JSON object in this exact structure, no extra keys or explanation:
{
  "en": { "title": "...", "meta_title": "...", "meta_description": "...", "nav_label": "..." },
  "pt": { "title": "...", "meta_title": "...", "meta_description": "...", "nav_label": "..." }
}
Keep translations natural and professional. If a source field is empty, return an empty string.
PROMPT;

        $userPrompt = json_encode([
            'title'            => $source['title']            ?? '',
            'meta_title'       => $source['meta_title']       ?? '',
            'meta_description' => $source['meta_description'] ?? '',
            'nav_label'        => $source['nav_label']        ?? '',
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->client->generateStructuredLanding([
            'system_prompt' => $systemPrompt,
            'user_prompt'   => $userPrompt,
        ]);

        $content = $response['content'];

        return [
            'en' => [
                'title'            => (string) ($content['en']['title']            ?? ''),
                'meta_title'       => (string) ($content['en']['meta_title']       ?? ''),
                'meta_description' => (string) ($content['en']['meta_description'] ?? ''),
                'nav_label'        => (string) ($content['en']['nav_label']        ?? ''),
            ],
            'pt' => [
                'title'            => (string) ($content['pt']['title']            ?? ''),
                'meta_title'       => (string) ($content['pt']['meta_title']       ?? ''),
                'meta_description' => (string) ($content['pt']['meta_description'] ?? ''),
                'nav_label'        => (string) ($content['pt']['nav_label']        ?? ''),
            ],
        ];
    }

    /**
     * Translate a single block's content fields from PL to EN and PT.
     * Works with any block type — translates all *_pl keys to *_en and *_pt equivalents.
     *
     * @throws LandingPageGenerationException
     */
    public function translateBlock(string $blockType, array $content): array
    {
        // Extract only PL-keyed values to translate
        $plFields = [];
        foreach ($content as $key => $value) {
            if (str_ends_with($key, '_pl') && is_string($value) && $value !== '') {
                $plFields[substr($key, 0, -3)] = $value;
            }
        }

        // Recursively translate items/packages/rows arrays
        $items = [];
        foreach (['items', 'packages', 'rows', 'columns'] as $arrayKey) {
            if (isset($content[$arrayKey]) && is_array($content[$arrayKey])) {
                $items[$arrayKey] = $content[$arrayKey];
            }
        }

        if (empty($plFields) && empty($items)) {
            return $content;
        }

        $systemPrompt = <<<PROMPT
You are a professional translator for a web agency website.
You receive a JSON object with Polish text fields for a "{$blockType}" content block.
Translate all field values from Polish into English (en) and Portuguese (pt).
Return ONLY a valid JSON object in this exact structure:
{
  "en": { <same keys as input, values in English> },
  "pt": { <same keys as input, values in Portuguese> }
}
For array fields (items, packages, rows, columns): translate every *_pl sub-field in each element.
Keep translations natural and professional. Preserve line breaks. If a source field is empty, return "".
PROMPT;

        $userPrompt = json_encode(array_merge($plFields, $items), JSON_UNESCAPED_UNICODE);

        $response = $this->client->generateStructuredLanding([
            'system_prompt' => $systemPrompt,
            'user_prompt'   => $userPrompt,
        ]);

        $translated = $response['content'];

        // Merge translations back into the content array
        $updated = $content;

        foreach (['en', 'pt'] as $locale) {
            if (! isset($translated[$locale])) {
                continue;
            }
            foreach ($translated[$locale] as $key => $value) {
                if (is_string($value)) {
                    $updated["{$key}_{$locale}"] = $value;
                }
            }
            // Handle array fields
            foreach (['items', 'packages', 'rows', 'columns'] as $arrayKey) {
                if (isset($translated[$locale][$arrayKey]) && is_array($translated[$locale][$arrayKey])) {
                    // Merge translated sub-fields back into existing items
                    foreach ($translated[$locale][$arrayKey] as $i => $translatedItem) {
                        if (! isset($updated[$arrayKey][$i])) {
                            continue;
                        }
                        foreach ($translatedItem as $subKey => $subValue) {
                            if (is_string($subValue)) {
                                $updated[$arrayKey][$i]["{$subKey}_{$locale}"] = $subValue;
                            }
                        }
                    }
                }
            }
        }

        return $updated;
    }
}
