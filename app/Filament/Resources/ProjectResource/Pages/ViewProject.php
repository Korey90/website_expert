<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ProjectResource;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\ProjectTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // ── Quick Actions ──────────────────────────────────────────────

            Action::make('manageTasks')
                ->label('Task Board')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->url(fn () => ProjectResource::getUrl('tasks', ['record' => $this->record])),

            Action::make('applyTemplate')
                ->label('Apply Template')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => ProjectTemplate::where('is_active', true)->exists())
                ->form([
                    Select::make('template_id')
                        ->label('Project Template')
                        ->options(fn () => ProjectTemplate::where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => $this->record->template_id)
                        ->required()
                        ->searchable(),
                    Toggle::make('clear_existing')
                        ->label('Remove existing phases & tasks first')
                        ->helperText('If OFF, phases from the template will be added on top of existing ones.')
                        ->default(fn () => $this->record->phases()->count() > 0),
                ])
                ->modalHeading('Apply Project Template')
                ->modalDescription('Phases and tasks from the selected template will be created in this project.')
                ->modalSubmitActionLabel('Apply')
                ->action(function (array $data) {
                    $template = ProjectTemplate::find($data['template_id']);
                    if (! $template) {
                        Notification::make()->title('Template not found')->danger()->send();
                        return;
                    }

                    if ($data['clear_existing']) {
                        // Delete tasks first (FK), then phases
                        ProjectTask::where('project_id', $this->record->id)
                            ->whereIn('phase_id', $this->record->phases()->pluck('id'))
                            ->delete();
                        $this->record->phases()->delete();
                    }

                    $phaseCount = 0;
                    $taskCount  = 0;

                    foreach ($template->phases as $phase) {
                        $createdPhase = ProjectPhase::create([
                            'project_id'  => $this->record->id,
                            'name'        => $phase['name'],
                            'description' => $phase['description'] ?? null,
                            'order'       => $phase['order'],
                            'status'      => 'pending',
                        ]);
                        $phaseCount++;

                        foreach ($phase['tasks'] ?? [] as $i => $task) {
                            ProjectTask::create([
                                'project_id'  => $this->record->id,
                                'phase_id'    => $createdPhase->id,
                                'title'       => $task['title'],
                                'description' => $task['description'] ?? null,
                                'priority'    => $task['priority'] ?? 'medium',
                                'status'      => 'todo',
                                'order'       => $i + 1,
                            ]);
                            $taskCount++;
                        }
                    }

                    // Update template_id on the project
                    $this->record->update(['template_id' => $data['template_id']]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Template applied')
                        ->body("{$phaseCount} phases and {$taskCount} tasks created.")
                        ->success()
                        ->send();
                }),

            Action::make('markActive')
                ->label('Mark Active')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['draft', 'on_hold']))
                ->action(function () {
                    $this->record->update(['status' => 'active']);
                    $this->record->refresh();
                    Notification::make()->title('Project is now Active')->success()->send();
                }),

            Action::make('markOnHold')
                ->label('Put On Hold')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->modalHeading('Put project on hold?')
                ->modalDescription('The project status will be changed to "On Hold".')
                ->action(function () {
                    $this->record->update(['status' => 'on_hold']);
                    $this->record->refresh();
                    Notification::make()->title('Project put on hold')->warning()->send();
                }),

            Action::make('markCompleted')
                ->label('Mark Complete')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => in_array($this->record->status, ['active', 'on_hold']))
                ->requiresConfirmation()
                ->modalHeading('Mark project as completed?')
                ->modalDescription('Today will be recorded as the completion date. This action can be reversed by editing the project.')
                ->action(function () {
                    $this->record->update(['status' => 'completed', 'completed_at' => now()]);
                    $this->record->refresh();
                    Notification::make()->title('Project marked as Completed 🎉')->success()->send();
                }),

            Action::make('markCancelled')
                ->label('Cancel Project')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !in_array($this->record->status, ['cancelled', 'completed']))
                ->requiresConfirmation()
                ->modalHeading('Cancel this project?')
                ->modalDescription('The project will be marked as Cancelled. You can restore it by editing later.')
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    $this->record->refresh();
                    Notification::make()->title('Project cancelled')->danger()->send();
                }),

            Action::make('newInvoice')
                ->label('Create Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->url(fn () => InvoiceResource::getUrl('create') . '?project_id=' . $this->record->id . '&client_id=' . $this->record->client_id),

            // ── Record management ──────────────────────────────────────────
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
