<?php

namespace App\Services\LandingPage;

use App\Exceptions\LandingPageGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class OpenAiLandingClient
{
    public function generateStructuredLanding(array $payload): array
    {
        return $this->sendMessages([
            ['role' => 'system', 'content' => $payload['system_prompt']],
            ['role' => 'user', 'content' => $payload['user_prompt']],
        ]);
    }

    public function regenerateSection(array $payload): array
    {
        return $this->sendMessages([
            ['role' => 'system', 'content' => $payload['system_prompt']],
            ['role' => 'user', 'content' => $payload['user_prompt']],
        ]);
    }

    /**
     * @param  list<array{role:string,content:string}>  $messages
     */
    private function sendMessages(array $messages): array
    {
        $config = config('services.openai');
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($apiKey === '') {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.not_configured'),
                'openai_not_configured',
                503,
            );
        }

        $maxAttempts = max(1, (int) ($config['retry_times'] ?? 2) + 1);
        $retrySleepMs = max(0, (int) ($config['retry_sleep_ms'] ?? 750));
        $timeout = max(5, (int) ($config['timeout'] ?? 30));
        $endpoint = rtrim((string) ($config['base_url'] ?? 'https://api.openai.com/v1'), '/');
        $model = (string) ($config['model'] ?? 'gpt-5.4-mini');

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::baseUrl($endpoint)
                    ->acceptJson()
                    ->withToken($apiKey)
                    ->timeout($timeout)
                    ->post('chat/completions', [
                        'model' => $model,
                        'temperature' => 0.7,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => $messages,
                    ]);

                if ($response->successful()) {
                    return $this->parseResponse($response->json(), $model);
                }

                if ($attempt < $maxAttempts && $this->shouldRetryStatus($response->status())) {
                    usleep($retrySleepMs * 1000);
                    continue;
                }

                throw new LandingPageGenerationException(
                    Arr::get($response->json(), 'error.message', __('landing_pages.ai.errors.generation_failed')),
                    Arr::get($response->json(), 'error.code', 'openai_request_failed'),
                    $this->mapStatusCode($response->status()),
                );
            } catch (ConnectionException $exception) {
                $lastException = $exception;

                if ($attempt < $maxAttempts) {
                    usleep($retrySleepMs * 1000);
                    continue;
                }
            }
        }

        throw new LandingPageGenerationException(
            __('landing_pages.ai.errors.unreachable'),
            'openai_unreachable',
            503,
            $lastException,
        );
    }

    private function parseResponse(array $payload, string $fallbackModel): array
    {
        $content = data_get($payload, 'choices.0.message.content');

        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (! is_string($content) || trim($content) === '') {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.empty_response'),
                'openai_empty_response',
                502,
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new LandingPageGenerationException(
                __('landing_pages.ai.errors.invalid_json'),
                'openai_invalid_json',
                502,
            );
        }

        return [
            'content' => $decoded,
            'model' => (string) data_get($payload, 'model', $fallbackModel),
            'tokens_input' => (int) data_get($payload, 'usage.prompt_tokens', 0),
            'tokens_output' => (int) data_get($payload, 'usage.completion_tokens', 0),
        ];
    }

    private function shouldRetryStatus(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    private function mapStatusCode(int $status): int
    {
        return in_array($status, [401, 403, 404, 422, 429, 500, 502, 503, 504], true)
            ? $status
            : 502;
    }
}