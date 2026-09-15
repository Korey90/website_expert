<?php

namespace App\Data\Seo;

final readonly class SerpSearchResult
{
    /**
     * @param  SerpOrganicResult[]  $organicResults
     */
    public function __construct(
        public string $keyword,
        public string $location,
        public string $device,
        public string $language,
        public array $organicResults,
        public string $provider,
    ) {}

    public function toArray(): array
    {
        return [
            'keyword' => $this->keyword,
            'location' => $this->location,
            'device' => $this->device,
            'language' => $this->language,
            'organic_results' => array_map(fn (SerpOrganicResult $r) => $r->toArray(), $this->organicResults),
            'provider' => $this->provider,
        ];
    }
}
