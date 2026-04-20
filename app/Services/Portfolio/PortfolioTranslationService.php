<?php

namespace App\Services\Portfolio;

use App\Exceptions\LandingPageGenerationException;
use App\Services\LandingPage\OpenAiLandingClient;

class PortfolioTranslationService
{
    public function __construct(
        private readonly OpenAiLandingClient $client,
    ) {}

    /**
     * Translate PL portfolio fields into EN and PT using OpenAI.
     *
     * @param  array{title: string, tag: string, description: string, result: string}  $source  Polish source
     * @return array{en: array{title: string, tag: string, description: string, result: string}, pt: array{title: string, tag: string, description: string, result: string}}
     *
     * @throws LandingPageGenerationException
     */
    public function translate(array $source): array
    {
        $systemPrompt = <<<'PROMPT'
You are a professional translator for a web agency portfolio.
Translate the provided fields from Polish into English (en) and Portuguese (pt).
Return ONLY a valid JSON object in this exact structure, with no extra keys or explanation:
{
  "en": { "title": "...", "tag": "...", "description": "...", "result": "..." },
  "pt": { "title": "...", "tag": "...", "description": "...", "result": "..." }
}
Keep translations natural and professional. Preserve line breaks (\n) in the "result" field.
If a source field is empty, return an empty string for that field.
PROMPT;

        $userPrompt = json_encode([
            'title'       => $source['title']       ?? '',
            'tag'         => $source['tag']         ?? '',
            'description' => $source['description'] ?? '',
            'result'      => $source['result']      ?? '',
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->client->generateStructuredLanding([
            'system_prompt' => $systemPrompt,
            'user_prompt'   => $userPrompt,
        ]);

        $content = $response['content'];

        return [
            'en' => [
                'title'       => (string) ($content['en']['title']       ?? ''),
                'tag'         => (string) ($content['en']['tag']         ?? ''),
                'description' => (string) ($content['en']['description'] ?? ''),
                'result'      => (string) ($content['en']['result']      ?? ''),
            ],
            'pt' => [
                'title'       => (string) ($content['pt']['title']       ?? ''),
                'tag'         => (string) ($content['pt']['tag']         ?? ''),
                'description' => (string) ($content['pt']['description'] ?? ''),
                'result'      => (string) ($content['pt']['result']      ?? ''),
            ],
        ];
    }
}
