<?php

namespace App\Services\LandingPage;

use App\Models\LandingPage;
use App\Models\LandingPageSection;
use DomainException;

class LandingPageSectionService
{
    public function __construct(
        private readonly PublicLandingPageService $publicLandingPageService,
    ) {}

    /**
     * Add a new section to the given landing page.
     */
    public function create(LandingPage $page, array $data): LandingPageSection
    {
        $this->validateContentForType($data['type'], $data['content'] ?? []);

        $maxOrder = $page->sections()->max('order') ?? -1;

        $section                   = new LandingPageSection();
        $section->landing_page_id  = $page->id;
        $section->type             = $data['type'];
        $section->order            = $data['order'] ?? ($maxOrder + 1);
        $section->is_visible       = $data['is_visible'] ?? true;
        $section->content          = $data['content'] ?? $section->getDefaultContent($data['type']);
        $section->settings         = $data['settings'] ?? [];
        $section->save();

        $this->publicLandingPageService->forgetCacheFor($page);

        return $section;
    }

    /**
     * Update an existing section.
     */
    public function update(LandingPageSection $section, array $data): LandingPageSection
    {
        if (isset($data['content'])) {
            $this->validateContentForType($section->type, $data['content']);
        }

        $fillable = array_filter([
            'order'      => $data['order'] ?? null,
            'is_visible' => $data['is_visible'] ?? null,
            'content'    => $data['content'] ?? null,
            'settings'   => $data['settings'] ?? null,
        ], fn ($v) => $v !== null);

        $section->fill($fillable);
        $section->save();

        $this->publicLandingPageService->forgetCacheFor($section->landingPage);

        return $section->refresh();
    }

    /**
        $this->publicLandingPageService->forgetCacheFor($section->landingPage);
     * Delete a section.
     *
     * @throws DomainException when trying to remove the only form section on a published page.
     */
    public function delete(LandingPageSection $section): void
    {
        if (
            $section->type === 'form'
            && $section->landingPage->status === LandingPage::STATUS_PUBLISHED
        ) {
            $formCount = $section->landingPage
                ->sections()
                ->where('type', 'form')
                ->count();

            if ($formCount <= 1) {
                throw new DomainException(__('landing_pages.errors.cannot_delete_last_form'));
            }
        }

        $section->delete();
    }

    /**
     * Validate the content array for the given section type.
     *
     * @throws DomainException on invalid/unsupported type.
     */
    public function validateContentForType(string $type, array $content): void
    {
        $allowedTypes = config('landing_pages.section_types', []);

        if (! array_key_exists($type, $allowedTypes)) {
            throw new DomainException(__('landing_pages.errors.invalid_section_type', ['type' => $type]));
        }

        if ($type === 'text') {
            if (isset($content['html'])) {
                $allowed = '<b><strong><i><em><u><a><br><p><ul><ol><li><h2><h3><h4><blockquote>';
                $content['html'] = strip_tags($content['html'], $allowed);
            }
        }

        if ($type === 'video') {
            $url              = $content['video_url'] ?? $content['url'] ?? '';
            $allowedDomains   = config('landing_pages.allowed_video_domains', []);
            $parsedHost       = parse_url($url, PHP_URL_HOST);

            if ($url !== '' && ! in_array($parsedHost, $allowedDomains, true)) {
                throw new DomainException(
                    __('landing_pages.errors.invalid_video_domain', ['domain' => $parsedHost])
                );
            }
        }
    }
}
