<div x-data="{ open: false }" class="admin-pinned-notes">
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open"
        class="admin-icon-button"
        aria-label="{{ __('admin.pinned_notes.label') }}"
        title="{{ __('admin.pinned_notes.label') }}"
    >
        <x-filament::icon icon="heroicon-o-bookmark" class="admin-topbar-icon" />

        @if($pinnedNotes->isNotEmpty())
            <span class="admin-notification-badge" aria-hidden="true">
                {{ $pinnedNotes->count() > 9 ? '9+' : $pinnedNotes->count() }}
            </span>
            <span class="sr-only">{{ trans_choice('admin.pinned_notes.count', $pinnedNotes->count(), ['count' => $pinnedNotes->count()]) }}</span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        x-transition
        class="admin-topbar-dropdown admin-pinned-notes-dropdown"
    >
        <div class="admin-topbar-dropdown-header admin-pinned-notes-header">
            <div class="admin-topbar-dropdown-heading">
                <x-filament::icon icon="heroicon-o-bookmark" class="admin-topbar-icon admin-pinned-notes-icon" />
                <p class="admin-topbar-dropdown-title">{{ __('admin.pinned_notes.heading') }}</p>
            </div>

            @if($pinnedNotes->isNotEmpty())
                <span class="admin-count-badge">{{ $pinnedNotes->count() }}</span>
            @endif
        </div>

        <div class="admin-pinned-notes-list">
            @forelse($pinnedNotes as $note)
                @if($note->lead)
                    <article class="admin-pinned-note">
                        <span class="admin-pinned-note-dot" aria-hidden="true"></span>

                        <a
                            href="{{ route('filament.admin.resources.leads.view', $note->lead) }}"
                            @click="open = false"
                            class="admin-pinned-note-link"
                        >
                            <span class="admin-pinned-note-title">{{ $note->lead->title }}</span>
                            <span class="admin-pinned-note-content">{{ \Illuminate\Support\Str::limit(strip_tags($note->content), 90) }}</span>
                            <span class="admin-pinned-note-meta">
                                @if($note->user){{ $note->user->name }} · @endif{{ $note->created_at->diffForHumans() }}
                            </span>
                        </a>

                        <form method="POST" action="{{ route('lead-notes.unpin', $note) }}" class="admin-unpin-form">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="admin-unpin-button"
                                aria-label="{{ __('admin.pinned_notes.unpin', ['lead' => $note->lead->title]) }}"
                                title="{{ __('admin.pinned_notes.unpin_short') }}"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="admin-unpin-icon" />
                            </button>
                        </form>
                    </article>
                @endif
            @empty
                <div class="admin-empty-state">
                    <span class="admin-empty-state-icon">
                        <x-filament::icon icon="heroicon-o-bookmark" class="admin-topbar-icon" />
                    </span>
                    <p class="admin-empty-state-title">{{ __('admin.pinned_notes.empty') }}</p>
                    <p class="admin-empty-state-description">{{ __('admin.pinned_notes.empty_hint') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
