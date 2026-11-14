@php
    $status = $record->status;
    $taskBoardUrl = \App\Filament\Resources\ProjectResource::getUrl('tasks', ['record' => $record]);
    $canEditProject = \App\Filament\Resources\ProjectResource::canEdit($record);
@endphp

<div class="admin-project-actions">
    <a href="{{ $taskBoardUrl }}" class="admin-project-action">
        <x-filament::icon icon="heroicon-o-view-columns" class="admin-project-action-icon" />
        <span>{{ __('admin.pilot.projects.actions.task_board') }}</span>
    </a>

    @if($canEditProject && in_array($status, ['draft', 'on_hold'], true))
        <button wire:click="updateProjectStatus('active')" type="button" class="admin-project-action admin-project-action--success">
            <x-filament::icon icon="heroicon-o-play" class="admin-project-action-icon" />
            <span>{{ __('admin.pilot.projects.actions.mark_active') }}</span>
        </button>
    @endif

    @if($canEditProject && $status === 'active')
        <button
            wire:click="updateProjectStatus('on_hold')"
            wire:confirm="{{ __('admin.pilot.projects.confirmations.on_hold') }}"
            type="button"
            class="admin-project-action admin-project-action--warning"
        >
            <x-filament::icon icon="heroicon-o-pause" class="admin-project-action-icon" />
            <span>{{ __('admin.pilot.projects.actions.put_on_hold') }}</span>
        </button>
    @endif

    @if($canEditProject && in_array($status, ['active', 'on_hold'], true))
        <button
            wire:click="updateProjectStatus('completed')"
            wire:confirm="{{ __('admin.pilot.projects.confirmations.complete') }}"
            type="button"
            class="admin-project-action admin-project-action--primary"
        >
            <x-filament::icon icon="heroicon-o-check-circle" class="admin-project-action-icon" />
            <span>{{ __('admin.pilot.projects.actions.mark_complete') }}</span>
        </button>
    @endif

    @if($canEditProject && ! in_array($status, ['cancelled', 'completed'], true))
        <button
            wire:click="updateProjectStatus('cancelled')"
            wire:confirm="{{ __('admin.pilot.projects.confirmations.cancel') }}"
            type="button"
            class="admin-project-action admin-project-action--danger"
        >
            <x-filament::icon icon="heroicon-o-x-circle" class="admin-project-action-icon" />
            <span>{{ __('admin.pilot.projects.actions.cancel') }}</span>
        </button>
    @endif
</div>
