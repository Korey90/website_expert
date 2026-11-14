@auth
    @php
        $businesses = auth()->user()->businesses()
            ->wherePivot('is_active', true)
            ->where('businesses.is_active', true)
            ->orderBy('business_users.id')
            ->get();
        $currentBusiness = currentBusiness();
    @endphp

    @if($currentBusiness)
        <div x-data="{ open: false }" class="admin-workspace-switcher">
            @if($businesses->count() > 1)
                <button
                    type="button"
                    @click="open = ! open"
                    :aria-expanded="open"
                    class="admin-workspace-trigger"
                    aria-label="{{ __('admin.workspace.current', ['name' => $currentBusiness->name]) }}"
                    title="{{ __('admin.workspace.current', ['name' => $currentBusiness->name]) }}"
                >
                    <x-filament::icon icon="heroicon-o-building-office-2" class="admin-topbar-icon" />
                    <span class="admin-workspace-copy">
                        <span class="admin-workspace-eyebrow">{{ __('admin.workspace.label') }}</span>
                        <span class="admin-workspace-name">{{ $currentBusiness->name }}</span>
                    </span>
                    <x-filament::icon icon="heroicon-m-chevron-down" class="admin-workspace-chevron" />
                </button>
            @else
                <div
                    class="admin-workspace-trigger"
                    aria-label="{{ __('admin.workspace.current', ['name' => $currentBusiness->name]) }}"
                    title="{{ __('admin.workspace.current', ['name' => $currentBusiness->name]) }}"
                >
                    <x-filament::icon icon="heroicon-o-building-office-2" class="admin-topbar-icon" />
                    <span class="admin-workspace-copy">
                        <span class="admin-workspace-eyebrow">{{ __('admin.workspace.label') }}</span>
                        <span class="admin-workspace-name">{{ $currentBusiness->name }}</span>
                    </span>
                </div>
            @endif

            @if($businesses->count() > 1)
                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    @keydown.escape.window="open = false"
                    x-transition
                    class="admin-topbar-dropdown admin-workspace-dropdown"
                >
                    <div class="admin-topbar-dropdown-header">
                        <p class="admin-topbar-dropdown-title">{{ __('admin.workspace.choose') }}</p>
                        <p class="admin-topbar-dropdown-description">{{ __('admin.workspace.isolation_notice') }}</p>
                    </div>

                    <div class="admin-topbar-dropdown-list">
                        @foreach($businesses as $business)
                            <form method="POST" action="{{ route('business.switch', $business) }}">
                                @csrf
                                <button type="submit" class="admin-workspace-option">
                                    <span class="truncate">{{ $business->name }}</span>

                                    @if($business->is($currentBusiness))
                                        <span class="admin-status-badge">{{ __('admin.workspace.active') }}</span>
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
@endauth
