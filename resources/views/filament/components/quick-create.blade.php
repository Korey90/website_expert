@php
    $quickCreateItems = collect([
        ['label' => __('admin.quick_create.lead'), 'icon' => 'heroicon-o-user-plus', 'url' => \App\Filament\Resources\LeadResource::canCreate() ? \App\Filament\Resources\LeadResource::getUrl('create') : null],
        ['label' => __('admin.quick_create.client'), 'icon' => 'heroicon-o-building-office', 'url' => \App\Filament\Resources\ClientResource::canCreate() ? \App\Filament\Resources\ClientResource::getUrl('create') : null],
        ['label' => __('admin.quick_create.project'), 'icon' => 'heroicon-o-briefcase', 'url' => \App\Filament\Resources\ProjectResource::canCreate() ? \App\Filament\Resources\ProjectResource::getUrl('create') : null],
        ['label' => __('admin.quick_create.quote'), 'icon' => 'heroicon-o-document-text', 'url' => \App\Filament\Resources\QuoteResource::canCreate() ? \App\Filament\Resources\QuoteResource::getUrl('create') : null],
        ['label' => __('admin.quick_create.invoice'), 'icon' => 'heroicon-o-receipt-percent', 'url' => \App\Filament\Resources\InvoiceResource::canCreate() ? \App\Filament\Resources\InvoiceResource::getUrl('create') : null],
    ])->filter(fn (array $item): bool => filled($item['url']));
@endphp

@if($quickCreateItems->isNotEmpty())
    <div x-data="{ open: false }" class="admin-quick-create">
        <button
            type="button"
            @click="open = ! open"
            :aria-expanded="open"
            class="admin-quick-create-trigger"
            aria-label="{{ __('admin.quick_create.label') }}"
            title="{{ __('admin.quick_create.label') }}"
        >
            <x-filament::icon icon="heroicon-m-plus" class="admin-topbar-icon" />
            <span>{{ __('admin.quick_create.label') }}</span>
        </button>

        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            @keydown.escape.window="open = false"
            x-transition
            class="admin-topbar-dropdown admin-quick-create-dropdown"
        >
            <div class="admin-topbar-dropdown-header">
                <p class="admin-topbar-dropdown-title">{{ __('admin.quick_create.heading') }}</p>
            </div>

            <div class="admin-topbar-dropdown-list">
                @foreach($quickCreateItems as $item)
                    <a href="{{ $item['url'] }}" class="admin-quick-create-option" @click="open = false">
                        <x-filament::icon :icon="$item['icon']" class="admin-topbar-icon" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
