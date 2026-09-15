<?php

namespace App\Services\Seo;

use App\Data\Seo\SerpOrganicResult;
use App\Data\Seo\SerpSearchResult;
use App\Exceptions\Seo\SerpProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SERP provider backed by the DataForSEO "SERP Google Organic Live Advanced" endpoint.
 *
 * @see https://docs.dataforseo.com/v3/serp/google/organic/live/advanced/
 *
 * Only 'organic' typed items are counted, matching the "organic results" scope of the tool.
 * Any HTTP/timeout/unexpected-payload failure is surfaced as SerpProviderException so the
 * caller never reports a provider outage as a "not found" ranking.
 */
class DataForSeoProviderService implements SerpProviderInterface
{
    private const ENDPOINT = '/v3/serp/google/organic/live/advanced';

    public function searchOrganicResults(
        string $keyword,
        string $location,
        ?string $locationCode,
        string $device,
        string $language,
        int $topResults,
    ): SerpSearchResult {
        $config = config('services.dataforseo');

        $task = [
            'keyword' => $keyword,
            'language_code' => $language,
            'device' => $device === 'mobile' ? 'mobile' : 'desktop',
            'os' => $device === 'mobile' ? 'android' : 'windows',
            'depth' => min(max($topResults, 10), 100),
        ];

        if ($locationCode !== null) {
            $task['location_code'] = (int) $locationCode;
        } else {
            $task['location_name'] = $location;
        }

        try {
            $response = Http::withBasicAuth($config['login'] ?? '', $config['password'] ?? '')
                ->timeout((int) ($config['timeout'] ?? 20))
                ->baseUrl($config['base_url'] ?? 'https://api.dataforseo.com')
                ->post(self::ENDPOINT, [$task]);
        } catch (\Throwable $e) {
            Log::error('DataForSEO request failed', ['error' => $e->getMessage(), 'keyword' => $keyword]);
            throw new SerpProviderException('SERP provider request failed.', previous: $e);
        }

        if (! $response->successful()) {
            Log::error('DataForSEO HTTP error', ['status' => $response->status(), 'keyword' => $keyword]);
            throw new SerpProviderException("SERP provider HTTP error ({$response->status()}).");
        }

        $body = $response->json();
        $task0 = $body['tasks'][0] ?? null;

        if (! is_array($task0) || (int) ($task0['status_code'] ?? 0) !== 20000) {
            $message = $task0['status_message'] ?? ($body['status_message'] ?? 'Unknown SERP provider error');
            Log::error('DataForSEO task error', ['message' => $message, 'keyword' => $keyword]);
            throw new SerpProviderException("SERP provider task error: {$message}");
        }

        $items = $task0['result'][0]['items'] ?? [];

        $organic = [];
        foreach ($items as $item) {
            if (($item['type'] ?? null) !== 'organic') {
                continue;
            }

            $position = $item['rank_group'] ?? $item['rank_absolute'] ?? null;
            $url = $item['url'] ?? null;

            if ($position === null || $url === null) {
                continue;
            }

            $organic[] = new SerpOrganicResult(
                position: (int) $position,
                url: (string) $url,
                title: $item['title'] ?? null,
            );
        }

        return new SerpSearchResult(
            keyword: $keyword,
            location: $location,
            device: $device,
            language: $language,
            organicResults: $organic,
            provider: 'dataforseo',
        );
    }
}
