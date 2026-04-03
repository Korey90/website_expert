<?php

namespace App\Data\LandingPage;

final readonly class RegenerateLandingSectionData
{
    public function __construct(
        public string $sectionType,
        public ?string $instruction,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sectionType: $data['section_type'],
            instruction: $data['instruction'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'section_type' => $this->sectionType,
            'instruction' => $this->instruction,
        ];
    }
}