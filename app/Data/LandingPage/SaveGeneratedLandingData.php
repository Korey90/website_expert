<?php

namespace App\Data\LandingPage;

final readonly class SaveGeneratedLandingData
{
    /**
     * @param  array<string, mixed>|null  $meta
     * @param  list<array<string, mixed>>|null  $sections
     */
    public function __construct(
        public ?string $title,
        public ?string $slug,
        public ?array $meta,
        public ?array $sections,
        public ?string $language,
        public ?string $templateKey,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            slug: $data['slug'] ?? null,
            meta: $data['meta'] ?? null,
            sections: $data['sections'] ?? null,
            language: $data['language'] ?? null,
            templateKey: $data['template_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'meta' => $this->meta,
            'sections' => $this->sections,
            'language' => $this->language,
            'template_key' => $this->templateKey,
        ];
    }
}