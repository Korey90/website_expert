<?php

namespace App\Services\Leads;

use App\Services\LandingPage\PublicLandingPageService;

class PublicLeadCaptureService
{
    public function __construct(
        private readonly LeadService $leadService,
        private readonly LeadConsentService $leadConsentService,
        private readonly PublicLandingPageService $publicLandingPageService,
    ) {}

    /**
     * Resolve a published landing page, create the lead in CRM and return
     * response-ready payload for public form handlers.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $context
     * @return array{
     *     status: 'created'|'duplicate',
     *     lead_id: int,
     *     message: string,
     *     redirect_url: string|null
     * }
     */
    public function captureBySlug(string $landingPageSlug, array $validated, array $context): array
    {
        $page = $this->publicLandingPageService->resolvePublishedBySlug($landingPageSlug, withBusiness: true);

        $sourceData = [
            'ip_address'   => $context['ip_address'] ?? null,
            'user_agent'   => $context['user_agent'] ?? '',
            'referrer_url' => $context['referrer_url'] ?? null,
            'page_url'     => $context['page_url'] ?? null,
            'utm_source'   => $context['utm_source'] ?? null,
            'utm_medium'   => $context['utm_medium'] ?? null,
            'utm_campaign' => $context['utm_campaign'] ?? null,
            'utm_content'  => $context['utm_content'] ?? null,
            'utm_term'     => $context['utm_term'] ?? null,
        ];

        $locale = $context['locale'] ?? app()->getLocale();

        $consentData = [
            'given'           => (bool) ($context['consent'] ?? $validated['consent'] ?? false),
            'consent_text'    => $this->leadConsentService->getConsentTextForLocale($locale),
            'consent_version' => config('leads.consent_version', '1.0'),
            'source_url'      => $context['source_url'] ?? null,
            'ip_address'      => $context['ip_address'] ?? null,
            'locale'          => $locale,
        ];

        $result = $this->leadService->createFromLandingPage(
            $validated,
            $sourceData,
            $consentData,
            $page,
        );

        if ($result['status'] === 'created') {
            $page->increment('conversions_count');
        }

        $formSection = $page->formSection;

        return [
            'status'       => $result['status'],
            'lead_id'      => $result['lead_id'],
            'message'      => $formSection?->content['success_message']
                ?? $formSection?->settings['success_message']
                ?? __('landing_pages.lead_captured'),
            'redirect_url' => $formSection?->content['redirect_url']
                ?? $formSection?->settings['redirect_url']
                ?? $page->thank_you_url
                ?? null,
        ];
    }
}