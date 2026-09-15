<?php

namespace App\Data\Seo;

final readonly class RankCheckResult
{
    public function __construct(
        public string $status,       // found|not_found|failed
        public string $keyword,
        public string $domain,
        public string $location,
        public string $device,
        public string $language,
        public ?int $position,
        public ?string $rankingUrl,
        public string $provider,
        public bool $fromCache,
        public ?string $message = null,
    ) {}

    public static function found(
        string $keyword,
        string $domain,
        string $location,
        string $device,
        string $language,
        int $position,
        string $rankingUrl,
        string $provider,
        bool $fromCache = false,
    ): self {
        return new self(
            status: 'found',
            keyword: $keyword,
            domain: $domain,
            location: $location,
            device: $device,
            language: $language,
            position: $position,
            rankingUrl: $rankingUrl,
            provider: $provider,
            fromCache: $fromCache,
        );
    }

    public static function notFound(
        string $keyword,
        string $domain,
        string $location,
        string $device,
        string $language,
        string $provider,
        bool $fromCache = false,
    ): self {
        return new self(
            status: 'not_found',
            keyword: $keyword,
            domain: $domain,
            location: $location,
            device: $device,
            language: $language,
            position: null,
            rankingUrl: null,
            provider: $provider,
            fromCache: $fromCache,
        );
    }

    public static function failed(
        string $keyword,
        string $domain,
        string $location,
        string $device,
        string $language,
        string $provider,
        string $message,
    ): self {
        return new self(
            status: 'failed',
            keyword: $keyword,
            domain: $domain,
            location: $location,
            device: $device,
            language: $language,
            position: null,
            rankingUrl: null,
            provider: $provider,
            fromCache: false,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'keyword' => $this->keyword,
            'domain' => $this->domain,
            'location' => $this->location,
            'device' => $this->device,
            'language' => $this->language,
            'position' => $this->position,
            'ranking_url' => $this->rankingUrl,
            'provider' => $this->provider,
            'from_cache' => $this->fromCache,
            'message' => $this->message,
        ];
    }
}
