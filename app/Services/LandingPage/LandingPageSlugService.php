<?php

namespace App\Services\LandingPage;

use App\Models\Business;
use App\Models\LandingPage;
use Illuminate\Support\Str;

class LandingPageSlugService
{
    /** @var list<string> */
    private array $blacklist;

    public function __construct()
    {
        $this->blacklist = config('landing_pages.slug_blacklist', []);
    }

    /**
     * Generate a unique slug for the given title within the given business.
     */
    public function generate(string $title, Business $business): string
    {
        $base = Str::slug($title);

        if (empty($base)) {
            $base = 'page';
        }

        $slug = $this->ensureUnique($base, $business);

        return $slug;
    }

    /**
     * Check whether a slug is available for the given business.
     * Pass $excluding to allow the page's own current slug (update use-case).
     */
    public function validate(string $slug, Business $business, ?LandingPage $excluding = null): bool
    {
        if (! $this->isFormatValid($slug)) {
            return false;
        }

        if ($this->isBlacklisted($slug)) {
            return false;
        }

        return ! $this->existsGlobally($slug, $excluding);
    }

    /**
     * Checks only format (alphanumeric + hyphens, lowercase).
     */
    public function isFormatValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * Returns true if the slug is in the global blacklist.
     */
    public function isBlacklisted(string $slug): bool
    {
        return in_array($slug, $this->blacklist, true);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function ensureUnique(string $base, Business $business, ?LandingPage $excluding = null): string
    {
        if ($this->isBlacklisted($base)) {
            $base = $base . '-page';
        }

        if (! $this->existsGlobally($base, $excluding)) {
            return $base;
        }

        $i = 2;
        do {
            $candidate = $base . '-' . $i;
            $i++;
        } while ($this->existsGlobally($candidate, $excluding));

        return $candidate;
    }

    private function existsGlobally(string $slug, ?LandingPage $excluding = null): bool
    {
        $query = LandingPage::withTrashed()
            ->where('slug', $slug);

        if ($excluding) {
            $query->where('id', '!=', $excluding->id);
        }

        return $query->exists();
    }
}
