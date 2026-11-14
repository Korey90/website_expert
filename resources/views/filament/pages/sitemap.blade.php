<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Sitemap URL card --}}
        <x-filament::section>
            <x-slot name="heading">{{ __('admin.migration.marketing.sitemap.url_heading') }}</x-slot>
            <x-slot name="description">
                {{ __('admin.migration.marketing.sitemap.url_description') }}
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
            <x-slot name="heading">{{ __('admin.migration.marketing.sitemap.included_heading') }}</x-slot>
            <x-slot name="description">
                {{ __('admin.migration.marketing.sitemap.included_description') }}
            </x-slot>

            <ul class="space-y-3">
                @foreach([
                    ['label' => __('admin.migration.marketing.sitemap.static_pages'), 'items' => ['/', '/calculate']],
                    ['label' => __('admin.migration.marketing.sitemap.portfolio'), 'items' => ['/portfolio', '/portfolio/{slug} — ' . __('admin.migration.marketing.sitemap.active_featured_projects')]],
                    ['label' => __('admin.migration.marketing.sitemap.services'), 'items' => ['/services', '/services/{slug} — ' . __('admin.migration.marketing.sitemap.active_services')]],
                    ['label' => __('admin.migration.marketing.sitemap.cms_pages'), 'items' => ['/p/{slug} — ' . __('admin.migration.marketing.sitemap.cms_examples')]],
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
