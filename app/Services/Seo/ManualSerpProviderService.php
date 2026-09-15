<?php

namespace App\Services\Seo;

use App\Data\Seo\SerpOrganicResult;
use App\Data\Seo\SerpSearchResult;

/**
 * Free, deterministic stand-in for a paid SERP provider. Used when no SERP_PROVIDER
 * is configured so the Google Rank Checker works out-of-the-box without external cost.
 * Never returns the caller's own domain — real rankings require a real provider (e.g. DataForSEO).
 */
class ManualSerpProviderService implements SerpProviderInterface
{
    private const STUB_RESULT_COUNT = 20;

    public function searchOrganicResults(
        string $keyword,
        string $location,
        ?string $locationCode,
        string $device,
        string $language,
        int $topResults,
    ): SerpSearchResult {
        $seed = crc32(strtolower("{$keyword}|{$location}|{$device}|{$language}"));
        mt_srand($seed);

        $count = min(self::STUB_RESULT_COUNT, $topResults);
        $results = [];

        for ($position = 1; $position <= $count; $position++) {
            $slug = substr(md5($seed.$position), 0, 8);
            $results[] = new SerpOrganicResult(
                position: $position,
                url: "https://example-{$slug}.com/",
                title: null,
            );
        }

        mt_srand(); // restore random seeding for the rest of the request

        return new SerpSearchResult(
            keyword: $keyword,
            location: $location,
            device: $device,
            language: $language,
            organicResults: $results,
            provider: 'manual',
        );
    }
}
