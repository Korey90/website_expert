<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Website Expert') }}</title>

        @php
            $noIndexPrefixes = ['portal.', 'onboarding.', 'reports.', 'admin.'];
            $noIndexNames    = [
                'login', 'register', 'password.request', 'password.reset',
                'verification.notice', 'profile.edit', 'profile.update', 'profile.destroy',
                'profile.social.unlink', 'profile.social.connect',
                'invoice.pdf', 'lead-notes.unpin',
                'business.edit', 'business.update', 'business.logo.upload', 'business.logo.delete',
                'business.profile.edit', 'business.profile.update', 'business.profile.completion',
                'business.api-tokens.index', 'business.api-tokens.store', 'business.api-tokens.destroy',
                'leads.show', 'leads.assign', 'leads.stage', 'leads.won', 'leads.lost',
                'notification.follow', 'notification.mark-read',
                'social.redirect', 'social.callback',
                'dashboard',
            ];
            $currentName = Route::currentRouteName() ?? '';
            $isNoIndex   = collect($noIndexNames)->contains($currentName)
                        || collect($noIndexPrefixes)->contains(fn($p) => str_starts_with($currentName, $p));
        @endphp
        @if($isNoIndex)
            <meta name="robots" content="noindex, nofollow">
        @endif
        <link rel="canonical" href="{{ url()->current() }}">

        @php
            $locale = app()->getLocale();
            $hreflangs = [
                'en' => 'https://website-expert.uk/',
                'pl' => 'https://website-expert.uk/',
                'pt' => 'https://website-expert.uk/',
            ];
        @endphp
        @if(in_array($currentName, ['home']))
            <link rel="alternate" hreflang="en" href="{{ $hreflangs['en'] }}">
            <link rel="alternate" hreflang="pl" href="{{ $hreflangs['pl'] }}">
            <link rel="alternate" hreflang="pt" href="{{ $hreflangs['pt'] }}">
            <link rel="alternate" hreflang="x-default" href="{{ $hreflangs['en'] }}">
        @endif

        <!-- Fonts (self-hosted via Vite/fontsource) -->

        <!-- Cookie Consent Mode v2 — default denied BEFORE GTM loads -->
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('consent', 'default', {
                'analytics_storage':     'denied',
                'ad_storage':            'denied',
                'functionality_storage': 'denied',
                'security_storage':      'granted',
                'wait_for_update':       10000
            });
        </script>

        @php
            $gtmId = \App\Models\Setting::get('gtm_enabled') && \App\Models\Setting::get('gtm_id')
                ? \App\Models\Setting::get('gtm_id')
                : null;
        @endphp

        @php
            $pixelId = \App\Models\Setting::get('pixel_enabled') && \App\Models\Setting::get('pixel_id')
                ? \App\Models\Setting::get('pixel_id')
                : null;
        @endphp
        @if($pixelId)
        <script>window._metaPixelId = '{{ $pixelId }}';</script>
        @endif

        @if($gtmId)
        <!-- Google Tag Manager (deferred to load event) -->
        <script>
        window.addEventListener('load', function() {
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{{ $gtmId }}');
        });
        </script>
        <!-- End Google Tag Manager -->
        @endif

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @if($gtmId)
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        @endif

        @inertia
    </body>
</html>
