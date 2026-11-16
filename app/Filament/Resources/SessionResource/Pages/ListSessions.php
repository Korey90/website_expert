<?php

namespace App\Filament\Resources\SessionResource\Pages;

use App\Filament\Resources\SessionResource;
use App\Models\Session;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSessions extends ListRecords
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('flush_inactive')
                ->label('Flush inactive sessions')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Flush inactive sessions?')
                ->modalDescription('This will permanently delete all sessions that have been inactive for more than 2 hours.')
                ->modalSubmitActionLabel('Flush')
                ->action(function (): void {
                    $count = Session::where('last_activity', '<', now()->subHours(2)->timestamp)->count();
                    Session::where('last_activity', '<', now()->subHours(2)->timestamp)->delete();

                    Notification::make()
                        ->title('Inactive sessions removed')
                        ->body("{$count} session(s) flushed.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
