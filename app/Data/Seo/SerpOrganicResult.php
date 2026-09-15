<?php

namespace App\Data\Seo;

final readonly class SerpOrganicResult
{
    public function __construct(
        public int $position,
        public string $url,
        public ?string $title = null,
    ) {}

    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'url' => $this->url,
            'title' => $this->title,
        ];
    }
}
