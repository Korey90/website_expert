<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Setting;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $supported = array_keys(config('languages'));
        $locale    = session('locale') ?? $request->getPreferredLanguage($supported) ?? $supported[0];
        if (! in_array($locale, $supported)) {
            $locale = $supported[0];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'locale'              => $locale,
            'available_locales'   => config('languages'),
            'tracking'            => $this->resolveTrackingSettings(),
            'portal_translations' => $this->resolvePortalTranslations($locale),
            'landing_page_translations' => $this->resolveTranslations('landing_pages', $locale),
        ];
    }

    private function resolveTranslations(string $group, string $locale): array
    {
        $path = lang_path("{$locale}/{$group}.php");
        if (file_exists($path)) {
            return require $path;
        }

        $fallback = lang_path("en/{$group}.php");

        return file_exists($fallback) ? require $fallback : [];
    }

    private function resolvePortalTranslations(string $locale): array
    {
        return $this->resolveTranslations('portal', $locale);
    }

    private function resolveTrackingSettings(): array
    {
        try {
            return [
                'gtm_enabled'            => (bool) Setting::get('gtm_enabled', false),
                'gtm_id'                 => Setting::get('gtm_id', ''),
                'ga4_enabled'            => (bool) Setting::get('ga4_enabled', false),
                'ga4_id'                 => Setting::get('ga4_id', ''),
                'pixel_enabled'          => (bool) Setting::get('pixel_enabled', false),
                'pixel_id'               => Setting::get('pixel_id', ''),
                'gads_enabled'           => (bool) Setting::get('gads_enabled', false),
                'gads_id'                => Setting::get('gads_id', ''),
                'cookie_consent_enabled' => (bool) Setting::get('cookie_consent_enabled', true),
            ];
        } catch (\Throwable) {
            return [
                'gtm_enabled'            => false,
                'gtm_id'                 => '',
                'ga4_enabled'            => false,
                'ga4_id'                 => '',
                'pixel_enabled'          => false,
                'pixel_id'               => '',
                'gads_enabled'           => false,
                'gads_id'                => '',
                'cookie_consent_enabled' => true,
            ];
        }
    }
}
