<?php

namespace App\Services\LandingPage;

use App\Events\LandingPagePublished;
use App\Models\Business;
use App\Models\LandingPage;
use App\Models\LandingPageGenerationVariant;
use App\Models\LandingPageSection;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class LandingPageService
{
    public function __construct(
        private readonly LandingPageSlugService $slugService,
        private readonly PublicLandingPageService $publicLandingPageService,
    ) {}

    /**
     * Create a new landing page (with optional template sections).
     */
    public function create(array $data, Business $business): LandingPage
    {
        return DB::transaction(function () use ($data, $business) {
            $slug = isset($data['slug']) && $data['slug'] !== ''
                ? $data['slug']
                : $this->slugService->generate($data['title'], $business);

            /** @var LandingPage $page */
            $page = LandingPage::create([
                'business_id'      => $business->id,
                'title'            => $data['title'],
                'slug'             => $slug,
                'description'      => $data['description'] ?? null,
                'language'         => $data['language'] ?? $business->locale ?? 'en',
                'template_key'     => $data['template_key'] ?? null,
                'conversion_goal'  => $data['conversion_goal'] ?? null,
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'custom_css'       => $data['custom_css'] ?? null,
                'settings'         => $data['settings'] ?? [],
                'ai_generated'     => $data['ai_generated'] ?? false,
                'status'           => LandingPage::STATUS_DRAFT,
            ]);

            if (! empty($data['template_key'])) {
                $this->initSectionsFromTemplate($page, $data['template_key']);
            }

            $this->publicLandingPageService->forgetCacheFor($page);

            return $page;
        });
    }

    /**
     * Update a landing page's attributes.
     *
     * @throws DomainException if slug change is attempted on a published page.
     */
    public function update(LandingPage $page, array $data): LandingPage
    {
        if (
            isset($data['slug'])
            && $data['slug'] !== ''
            && $data['slug'] !== $page->slug
            && $page->status === LandingPage::STATUS_PUBLISHED
        ) {
            throw new DomainException(__('landing_pages.errors.cannot_change_slug_published'));
        }

        return DB::transaction(function () use ($page, $data) {
            if (isset($data['slug']) && $data['slug'] !== '' && $data['slug'] !== $page->slug) {
                $data['slug'] = $this->slugService->generate($data['slug'], $page->business);
            }

            $page->fill(array_filter([
                'title'            => $data['title'] ?? null,
                'slug'             => $data['slug'] ?? null,
                'description'      => $data['description'] ?? null,
                'language'         => $data['language'] ?? null,
                'conversion_goal'  => $data['conversion_goal'] ?? null,
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'custom_css'       => $data['custom_css'] ?? null,
                'settings'         => $data['settings'] ?? null,
            ], fn ($v) => $v !== null));

            $page->save();

            $this->publicLandingPageService->forgetCacheFor($page);

            return $page->refresh();
        });
    }

    /**
     * Publish a landing page.
     *
     * @throws DomainException if page cannot be published.
     */
    public function publish(LandingPage $page, User $publishedBy): LandingPage
    {
        if (! $page->canBePublished()) {
            throw new DomainException(__('landing_pages.errors.cannot_publish'));
        }

        $page->publish();
        $this->publicLandingPageService->forgetCacheFor($page);

        LandingPagePublished::dispatch($page, $publishedBy);

        return $page->refresh();
    }

    /**
     * Unpublish a landing page (back to draft).
     */
    public function unpublish(LandingPage $page): LandingPage
    {
        $page->unpublish();
        $this->publicLandingPageService->forgetCacheFor($page);
        return $page->refresh();
    }

    /**
     * Soft-delete a landing page.
     */
    public function delete(LandingPage $page): void
    {
        $this->publicLandingPageService->forgetCacheFor($page);
        $page->delete();
    }

    /**
     * Reorder sections: $order is an array of section IDs in the desired order.
     *
     * @param  list<int>  $sectionIds
     */
    public function reorderSections(LandingPage $page, array $sectionIds): void
    {
        DB::transaction(function () use ($page, $sectionIds) {
            foreach ($sectionIds as $position => $sectionId) {
                LandingPageSection::where('landing_page_id', $page->id)
                    ->where('id', $sectionId)
                    ->update(['order' => $position]);
            }
        });

        $this->publicLandingPageService->forgetCacheFor($page);
    }

    /**
     * Create the default sections for the given template key.
     */
    public function initSectionsFromTemplate(LandingPage $page, string $templateKey): void
    {
        $templates = config('landing_pages.templates', []);

        if (! isset($templates[$templateKey])) {
            return;
        }

        $types = $templates[$templateKey]['sections'] ?? [];

        foreach ($types as $order => $type) {
            $section            = new LandingPageSection();
            $section->landing_page_id = $page->id;
            $section->type      = $type;
            $section->order     = $order;
            $section->is_visible = true;
            $section->content   = $section->getDefaultContent($type);
            $section->settings  = [];
            $section->save();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromGeneratedVariant(
        Business $business,
        LandingPageGenerationVariant $variant,
        array $payload,
    ): LandingPage {
        return DB::transaction(function () use ($business, $payload, $variant) {
            $title = (string) ($payload['title'] ?? $variant->title);
            $slugInput = (string) ($payload['slug_suggestion'] ?? $variant->slug_suggestion ?? $title);
            $slug = $this->slugService->generate($slugInput, $business);
            $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
            $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];

            /** @var LandingPage $page */
            $page = LandingPage::create([
                'business_id' => $business->id,
                'title' => $title,
                'slug' => $slug,
                'language' => $payload['language'] ?? $variant->language ?? $business->locale ?? 'en',
                'template_key' => $payload['template_key'] ?? $variant->template_key,
                'conversion_goal' => $meta['conversion_goal'] ?? null,
                'meta_title' => $meta['meta_title'] ?? null,
                'meta_description' => $meta['meta_description'] ?? null,
                'ai_generated' => true,
                'ai_generation_source' => 'prompt',
                'current_generation_id' => $variant->generation_id,
                'status' => LandingPage::STATUS_DRAFT,
            ]);

            foreach (array_values($sections) as $order => $sectionData) {
                LandingPageSection::create([
                    'landing_page_id' => $page->id,
                    'type' => $sectionData['type'],
                    'order' => $order,
                    'content' => $sectionData['content'] ?? [],
                    'settings' => $sectionData['settings'] ?? [],
                    'is_visible' => (bool) ($sectionData['settings']['visible'] ?? true),
                ]);
            }

            $this->publicLandingPageService->forgetCacheFor($page);

            return $page->refresh();
        });
    }
}
