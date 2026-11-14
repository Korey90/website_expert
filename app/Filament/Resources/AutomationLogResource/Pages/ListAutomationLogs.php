<?php

namespace App\Filament\Resources\AutomationLogResource\Pages;

use App\Filament\Resources\AutomationLogResource;
use App\Models\AutomationLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListAutomationLogs extends ListRecords
{
    protected static string $resource = AutomationLogResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.logs.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete_older_30')
                ->label(__('admin.migration.automation.logs.delete_old_label'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.migration.automation.logs.delete_old_heading'))
                ->modalDescription(__('admin.migration.automation.logs.delete_old_description'))
                ->visible(fn (): bool => AutomationLogResource::canDeleteAny())
                ->action(function (): void {
                    abort_unless(AutomationLogResource::canDeleteAny(), 403);

                    $count = AutomationLog::olderThanDays(30)->count();
                    AutomationLog::olderThanDays(30)->delete();

                    Notification::make()
                        ->title(trans_choice('admin.migration.automation.logs.delete_old_success', $count, ['count' => $count]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
