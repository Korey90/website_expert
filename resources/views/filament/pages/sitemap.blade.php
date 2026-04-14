<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Sitemap URL card --}}
        <x-filament::section>
            <x-slot name="heading">Sitemap URL</x-slot>
            <x-slot name="description">
                Generated dynamically from the database and cached for 1 hour.
                Use the <strong>Refresh Sitemap</strong> button above to clear the cache and ping search engines.
            </x-slot>

            <a
                href="{{ $this->sitemapUrl }}"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline break-all"
            >
                {{ $this->sitemapUrl }}
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </x-filament::section>

        {{-- What's included --}}
        <x-filament::section>
            <x-slot name="heading">What's included</x-slot>
            <x-slot name="description">
                Items are excluded automatically when marked inactive in their respective resources.
            </x-slot>

            <ul class="space-y-3">
                @foreach([
                    ['label' => 'Static pages',   'items' => ['/', '/calculate']],
                    ['label' => 'Portfolio',       'items' => ['/portfolio', '/portfolio/{slug} — active featured projects']],
                    ['label' => 'Services',        'items' => ['/services', '/services/{slug} — active services']],
                    ['label' => 'CMS pages',       'items' => ['/p/{slug} — privacy policy, terms & conditions, cookies…']],
                ] as $group)
                    <li>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">
                            {{ $group['label'] }}
                        </p>
                        <ul class="space-y-1.5">
                            @foreach($group['items'] as $item)
                                <li class="flex items-center gap-2">
                                    <x-filament::icon
                                        icon="heroicon-m-check-circle"
                                        class="w-4 h-4 shrink-0 text-success-500"
                                    />
                                    <span class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

    </div>
</x-filament-panels::page>
