@php
    $phases = $record->phases()->with('tasks')->orderBy('order')->get();
    $canEditProject = \App\Filament\Resources\ProjectResource::canEdit($record);
    $statusTones = [
        'pending' => 'neutral',
        'in_progress' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];
    $statusLabels = [
        'pending' => __('admin.pilot.projects.phases.status.pending'),
        'in_progress' => __('admin.pilot.projects.phases.status.in_progress'),
        'completed' => __('admin.pilot.projects.phases.status.completed'),
        'cancelled' => __('admin.pilot.projects.phases.status.cancelled'),
    ];
    $total = $phases->count();
    $done = $phases->where('status', 'completed')->count();
    $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
@endphp

@if($phases->isEmpty())
    <div class="admin-project-empty-state">
        <x-filament::icon icon="heroicon-o-queue-list" class="admin-project-empty-state-icon" />
        <p class="admin-project-empty-state-title">{{ __('admin.pilot.projects.phases.empty_heading') }}</p>
        <p class="admin-project-empty-state-description">{{ __('admin.pilot.projects.phases.empty_description') }}</p>
    </div>
@else
    <div class="admin-project-overall-progress">
        <div class="admin-project-progress-label">
            <span>{{ __('admin.pilot.projects.phases.overall_progress') }}</span>
            <span>{{ $pct }}%</span>
        </div>
        <progress
            class="admin-project-progress admin-project-progress--primary"
            value="{{ $pct }}"
            max="100"
            aria-label="{{ __('admin.pilot.projects.phases.overall_progress') }}"
        >{{ $pct }}%</progress>
    </div>

    <div class="admin-project-phases">
        @foreach($phases as $phase)
            @php
                $tone = $statusTones[$phase->status] ?? 'neutral';
                $tasks = $phase->tasks->count();
                $taskDone = $phase->tasks->where('status', 'done')->count();
                $taskPct = $tasks > 0 ? (int) round($taskDone / $tasks * 100) : 0;
            @endphp

            <article class="admin-project-phase admin-project-phase--{{ $tone }}">
                <div class="admin-project-phase-header">
                    <div class="admin-project-phase-title-group">
                        <span class="admin-project-phase-order">{{ $phase->order }}</span>
                        <h3 class="admin-project-phase-title">{{ $phase->name }}</h3>
                    </div>
                    <span class="admin-project-phase-task-count">
                        {{ $tasks > 0
                            ? __('admin.pilot.projects.phases.tasks_count', ['done' => $taskDone, 'total' => $tasks])
                            : __('admin.pilot.projects.phases.no_tasks') }}
                    </span>
                </div>

                @if($phase->description)
                    <p class="admin-project-phase-description">{{ $phase->description }}</p>
                @endif

                @if($tasks > 0)
                    <progress
                        class="admin-project-progress admin-project-progress--{{ $tone }} admin-project-progress--compact"
                        value="{{ $taskPct }}"
                        max="100"
                        aria-label="{{ __('admin.pilot.projects.phases.phase_progress', ['phase' => $phase->name]) }}"
                    >{{ $taskPct }}%</progress>
                @endif

                @if($canEditProject)
                    <div class="admin-project-phase-statuses" aria-label="{{ __('admin.pilot.projects.phases.change_status') }}">
                        @foreach($statusLabels as $statusKey => $statusLabel)
                            @php $isActive = $phase->status === $statusKey; @endphp
                            <button
                                wire:click="updatePhaseStatus({{ $phase->id }}, '{{ $statusKey }}')"
                                wire:loading.attr="disabled"
                                wire:target="updatePhaseStatus({{ $phase->id }}, '{{ $statusKey }}')"
                                type="button"
                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                @class([
                                    'admin-project-phase-status',
                                    'admin-project-phase-status--'.$statusTones[$statusKey],
                                    'admin-project-phase-status--active' => $isActive,
                                ])
                            >
                                <span class="admin-project-phase-status-dot" aria-hidden="true"></span>
                                {{ $statusLabel }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endif
