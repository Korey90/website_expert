<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('markSent')
                ->label('Mark Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'sent', 'sent_at' => now()]);
                    Notification::make()->success()->title('Contract marked as sent')->send();
                    $this->refreshFormData(['status', 'sent_at']);
                }),
            Action::make('markSigned')
                ->label('Mark Signed')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'sent')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'signed', 'signed_at' => now()]);
                    Notification::make()->success()->title('Contract marked as signed')->send();
                    $this->refreshFormData(['status', 'signed_at']);
                }),
            RestoreAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
