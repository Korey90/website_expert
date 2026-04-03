<?php

namespace App\Services\LandingPage;

use App\Models\LandingPage;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PublicLandingPageService
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function resolvePublishedBySlug(string $slug, bool $withBusiness = false): LandingPage
    {
        $cacheEnabled = (bool) config('landing_pages.public_cache.enabled', false);

        if (! $cacheEnabled) {
            return $this->queryPublishedBySlug($slug, $withBusiness);
        }

        $pageId = $this->cache->remember(
            $this->cacheKey($slug),
            now()->addSeconds((int) config('landing_pages.public_cache.ttl_seconds', 300)),
            fn () => $this->queryPublishedIdBySlug($slug),
        );

        return $this->queryPublishedById((int) $pageId, $withBusiness, $slug);
    }

    public function forgetCacheFor(LandingPage $page): void
    {
        $this->cache->forget($this->cacheKey($page->slug));
    }

    private function queryPublishedBySlug(string $slug, bool $withBusiness): LandingPage
    {
        $query = LandingPage::query()
            ->published()
            ->where('slug', $slug)
            ->with(['sections' => fn ($builder) => $builder->visible()->ordered()]);

        if ($withBusiness) {
            $query->with('business');
        }

        $page = $query->first();

        if (! $page) {
            throw (new ModelNotFoundException())->setModel(LandingPage::class, [$slug]);
        }

        return $page;
    }

    private function queryPublishedIdBySlug(string $slug): int
    {
        $pageId = LandingPage::query()
            ->published()
            ->where('slug', $slug)
            ->value('id');

        if (! $pageId) {
            throw (new ModelNotFoundException())->setModel(LandingPage::class, [$slug]);
        }

        return (int) $pageId;
    }

    private function queryPublishedById(int $pageId, bool $withBusiness, string $slug): LandingPage
    {
        $query = LandingPage::query()
            ->published()
            ->whereKey($pageId)
            ->with(['sections' => fn ($builder) => $builder->visible()->ordered()]);

        if ($withBusiness) {
            $query->with('business');
        }

        $page = $query->first();

        if (! $page) {
            $this->cache->forget($this->cacheKey($slug));
            throw (new ModelNotFoundException())->setModel(LandingPage::class, [$slug]);
        }

        return $page;
    }

    private function cacheKey(string $slug): string
    {
        return sprintf('landing-pages.public.id.%s', $slug);
    }
}