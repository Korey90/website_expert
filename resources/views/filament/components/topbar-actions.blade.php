<div class="admin-topbar-actions">
    @include('filament.components.quick-create')
    @include('filament.components.business-switcher')

    @if($canViewPinnedNotes)
        @include('filament.components.pinned-notes', ['pinnedNotes' => $pinnedNotes])
    @endif
</div>
