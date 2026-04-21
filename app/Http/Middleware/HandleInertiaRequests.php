<?php

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\NavItem;
use App\Models\Setting;
use App\Models\SiteSection;
use Illuminate\Http\Request;
use Inertia\Middleware;

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

        app()->setLocale($locale);

        return [
            ...parent::share($request),
            'auth' => [
                'user'                => $request->user(),
                'portal_capabilities' => $this->resolvePortalCapabilities($request),
            ],
            'locale'              => $locale,
            'available_locales'   => config('languages'),
            'nav_items'           => $this->resolveNavItems(),
            'nav_settings'        => $this->resolveNavSettings(),
            'tracking'            => $this->resolveTrackingSettings(),
            'portal_translations' => $this->resolvePortalTranslations($locale),
            'landing_page_translations' => $this->resolveTranslations('landing_pages', $locale),
        ];
    }

    private function resolvePortalCapabilities(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [
                'can_access_client_portal' => false,
                'can_access_workspace'     => false,
                'mode'                     => 'guest',
            ];
        }

        $canAccessWorkspace = $user->currentBusiness() !== null;
        $canAccessClientPortal = Client::where('portal_user_id', $user->id)->exists();

        $mode = match (true) {
            $canAccessWorkspace && $canAccessClientPortal => 'hybrid',
            $canAccessWorkspace                           => 'workspace',
            $canAccessClientPortal                        => 'client',
            default                                       => 'none',
        };

        return [
            'can_access_client_portal' => $canAccessClientPortal,
            'can_access_workspace'     => $canAccessWorkspace,
            'mode'                     => $mode,
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

    private function resolveNavItems(): array
    {
        try {
            $activeSectionKeys = SiteSection::where('is_active', true)
                ->pluck('key')
                ->all();

            return NavItem::active()
                ->ordered()
                ->get()
                ->filter(fn (NavItem $item) =>
                    blank($item->section_key) || in_array($item->section_key, $activeSectionKeys)
                )
                ->map(fn (NavItem $item) => [
                    'href'            => $item->href,
                    'label'           => $item->getTranslations('label'),
                    'section_key'     => $item->section_key,
                    'open_in_new_tab' => $item->open_in_new_tab,
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveNavSettings(): array
    {
        try {
            return [
                'brand_name'          => Setting::get('nav_brand_name',         'WebsiteExpert'),
                'cta_href'            => Setting::get('nav_cta_href',           '#contact'),
                'cta_text_pl'         => Setting::get('nav_cta_text_pl',        'Bezpłatna wycena'),
                'cta_text_en'         => Setting::get('nav_cta_text_en',        'Free Quote'),
                'cta_text_pt'         => Setting::get('nav_cta_text_pt',        'Orçamento Gratuito'),
                'show_cta_button'     => (bool) Setting::get('nav_show_cta_button',     true),
                'show_lang_switcher'  => (bool) Setting::get('nav_show_lang_switcher', true),
                'show_theme_toggle'   => (bool) Setting::get('nav_show_theme_toggle',  true),
                'show_client_portal'  => (bool) Setting::get('nav_show_client_portal', true),
            ];
        } catch (\Throwable) {
            return [
                'brand_name'          => 'WebsiteExpert',
                'cta_href'            => '#contact',
                'cta_text_pl'         => 'Bezpłatna wycena',
                'cta_text_en'         => 'Free Quote',
                'cta_text_pt'         => 'Orçamento Gratuito',
                'show_cta_button'     => true,
                'show_lang_switcher'  => true,
                'show_theme_toggle'   => true,
                'show_client_portal'  => true,
            ];
        }
    }
}
