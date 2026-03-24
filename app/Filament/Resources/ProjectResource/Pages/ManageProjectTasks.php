<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\ProjectTask;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class ManageProjectTasks extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.manage-project-tasks';

    protected static ?string $title = 'Task Board';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    // Form state for inline create modal
    public ?int $editingTaskId = null;
    public array $taskForm     = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string
    {
        return 'Task Board — ' . $this->getRecord()->title;
    }

    public function getGroupedTasks(): array
    {
        $statuses = ['todo', 'in_progress', 'review', 'done'];
        $tasks    = $this->getRecord()
            ->tasks()
            ->with(['assignedTo', 'phase'])
            ->leftJoin('project_phases', 'project_tasks.phase_id', '=', 'project_phases.id')
            ->orderBy('project_phases.order')
            ->orderBy('project_tasks.order')
            ->orderBy('project_tasks.created_at')
            ->select('project_tasks.*')
            ->get()
            ->groupBy('status')
            ->toArray();

        $result = [];
        foreach ($statuses as $status) {
            $result[$status] = $tasks[$status] ?? [];
        }
        return $result;
    }

    public function moveTask(int $taskId, string $status): void
    {
        $validStatuses = ['todo', 'in_progress', 'review', 'done'];
        if (! in_array($status, $validStatuses)) {
            return;
        }

        $task = ProjectTask::where('project_id', $this->getRecord()->id)
            ->findOrFail($taskId);

        $oldStatus = $task->status;
        $task->update([
            'status'       => $status,
            'completed_at' => $status === 'done' ? now() : null,
        ]);

        // Auto-update phase status based on task completion
        if ($task->phase_id) {
            $phase       = \App\Models\ProjectPhase::find($task->phase_id);
            $allTasks    = $phase?->tasks()->count() ?? 0;
            $doneTasks   = $phase?->tasks()->where('status', 'done')->count() ?? 0;
            $activeTasks = $phase?->tasks()->whereIn('status', ['in_progress', 'review'])->count() ?? 0;

            if ($phase && $allTasks > 0) {
                if ($doneTasks === $allTasks) {
                    $phase->update(['status' => 'completed']);
                } elseif (($activeTasks > 0 || $doneTasks > 0) && $phase->status === 'pending') {
                    $phase->update(['status' => 'in_progress']);
                } elseif ($doneTasks < $allTasks && $phase->status === 'completed') {
                    $phase->update(['status' => 'in_progress']);
                }
            }
        }

        Notification::make()
            ->success()
            ->title('Task moved')
            ->body('"' . $task->title . '" → ' . ucfirst(str_replace('_', ' ', $status)))
            ->send();
    }

    public function deleteTask(int $taskId): void
    {
        $task = ProjectTask::where('project_id', $this->getRecord()->id)
            ->findOrFail($taskId);

        $task->delete();

        Notification::make()
            ->success()
            ->title('Task deleted')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_project')
                ->label('Edit Project')
                ->icon('heroicon-o-pencil-square')
                ->url(ProjectResource::getUrl('edit', ['record' => $this->getRecord()]))
                ->color('gray'),

            Action::make('create_task')
                ->label('New Task')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->options([
                            'todo'        => 'To Do',
                            'in_progress' => 'In Progress',
                            'review'      => 'Review',
                            'done'        => 'Done',
                        ])
                        ->default('todo')
                        ->required(),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low'    => 'Low',
                            'medium' => 'Medium',
                            'high'   => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('medium')
                        ->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->label('Assignee')
                        ->options(User::pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date'),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    $this->getRecord()->tasks()->create($data);

                    Notification::make()
                        ->success()
                        ->title('Task created')
                        ->send();
                }),
        ];
    }
}
