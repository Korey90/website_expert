<?php namespace App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\ProjectTemplate;
use Filament\Resources\Pages\CreateRecord;
class CreateProject extends CreateRecord {
    protected static string $resource = ProjectResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        if ($record->template_id) {
            $template = ProjectTemplate::find($record->template_id);
            if ($template) {
                foreach ($template->phases as $phase) {
                    $createdPhase = ProjectPhase::create([
                        'project_id'  => $record->id,
                        'name'        => $phase['name'],
                        'description' => $phase['description'] ?? null,
                        'order'       => $phase['order'],
                        'status'      => 'pending',
                    ]);

                    foreach ($phase['tasks'] ?? [] as $i => $task) {
                        ProjectTask::create([
                            'project_id'  => $record->id,
                            'phase_id'    => $createdPhase->id,
                            'title'       => $task['title'],
                            'description' => $task['description'] ?? null,
                            'priority'    => $task['priority'] ?? 'medium',
                            'status'      => 'todo',
                            'order'       => $i + 1,
                        ]);
                    }
                }
            }
        }
    }
}
