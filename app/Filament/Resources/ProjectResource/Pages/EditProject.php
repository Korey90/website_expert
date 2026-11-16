<?php namespace App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditProject extends EditRecord {
    protected static string $resource = ProjectResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('taskBoard')
                ->label('Task Board')
                ->icon('heroicon-o-squares-2x2')
                ->url(fn () => ProjectResource::getUrl('tasks', ['record' => $this->getRecord()]))
                ->color('gray'),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
