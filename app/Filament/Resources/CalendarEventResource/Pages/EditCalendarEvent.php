<?php

namespace App\Filament\Resources\CalendarEventResource\Pages;

use App\Filament\Resources\CalendarEventResource;
use App\Services\Calendar\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCalendarEvent extends EditRecord
{
    protected static string $resource = CalendarEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGoogle')
                ->label('Sync to Google')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn () => app(GoogleCalendarService::class)
                    ->isConnected(auth()->id(), currentBusiness()?->id))
                ->action(function (): void {
                    $service    = app(GoogleCalendarService::class);
                    $googleId   = $service->pushEvent(
                        $this->record,
                        auth()->id(),
                        currentBusiness()?->id,
                    );

                    if ($googleId) {
                        Notification::make()->title('Synced to Google Calendar')->success()->send();
                    } else {
                        Notification::make()->title('Google sync failed')->danger()->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
