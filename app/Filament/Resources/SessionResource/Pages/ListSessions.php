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

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.sessions.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('flush_inactive')
                ->label(__('admin.migration.administration.sessions.flush_inactive_label'))
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('admin.migration.administration.sessions.flush_inactive_heading'))
                ->modalDescription(__('admin.migration.administration.sessions.flush_inactive_description'))
                ->modalSubmitActionLabel(__('admin.migration.administration.sessions.flush'))
                ->visible(fn (): bool => SessionResource::canDeleteAny())
                ->action(function (): void {
                    abort_unless(SessionResource::canDeleteAny(), 403);

                    $count = Session::where('last_activity', '<', now()->subHours(2)->timestamp)->count();
                    Session::where('last_activity', '<', now()->subHours(2)->timestamp)->delete();

                    Notification::make()
                        ->title(__('admin.migration.administration.sessions.flush_inactive_success'))
                        ->body(trans_choice('admin.migration.administration.sessions.sessions_removed', $count, ['count' => $count]))
                        ->success()
                        ->send();
                }),
            Action::make('revoke_guests')
                ->label(__('admin.migration.administration.sessions.revoke_guests_label'))
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('admin.migration.administration.sessions.revoke_guests_heading'))
                ->modalDescription(__('admin.migration.administration.sessions.revoke_guests_description'))
                ->modalSubmitActionLabel(__('admin.migration.administration.sessions.revoke_guests_submit'))
                ->visible(fn (): bool => SessionResource::canDeleteAny())
                ->action(function (): void {
                    abort_unless(SessionResource::canDeleteAny(), 403);

                    $count = Session::whereNull('user_id')->count();
                    Session::whereNull('user_id')->delete();

                    Notification::make()
                        ->title(__('admin.migration.administration.sessions.revoke_guests_success'))
                        ->body(trans_choice('admin.migration.administration.sessions.sessions_removed', $count, ['count' => $count]))
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
