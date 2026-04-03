<?php

namespace App\Data\LandingPage;

final readonly class GenerateLandingData
{
    /**
     * @param  list<string>|null  $includeSections
     */
    public function __construct(
        public string $goal,
        public ?string $description,
        public ?string $campaignName,
        public ?string $targetAudienceOverride,
        public ?string $offerSummary,
        public ?string $preferredLanguage,
        public ?string $templateKey,
        public ?array $includeSections,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goal: $data['goal'],
            description: $data['description'] ?? null,
            campaignName: $data['campaign_name'] ?? null,
            targetAudienceOverride: $data['target_audience_override'] ?? null,
            offerSummary: $data['offer_summary'] ?? null,
            preferredLanguage: $data['preferred_language'] ?? null,
            templateKey: $data['template_key'] ?? null,
            includeSections: $data['include_sections'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'goal' => $this->goal,
            'description' => $this->description,
            'campaign_name' => $this->campaignName,
            'target_audience_override' => $this->targetAudienceOverride,
            'offer_summary' => $this->offerSummary,
            'preferred_language' => $this->preferredLanguage,
            'template_key' => $this->templateKey,
            'include_sections' => $this->includeSections,
        ];
    }
}