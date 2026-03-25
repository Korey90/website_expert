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

    protected string $view = 'filament.resources.contract-resource.pages.view-contract';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('sendToPortal')
                ->label('Send to Portal')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Send Contract to Client Portal')
                ->modalDescription('The contract will be marked as Sent and become visible to the client in their portal for signing.')
                ->modalSubmitActionLabel('Yes, Send')
                ->action(function () {
                    $this->record->update(['status' => 'sent', 'sent_at' => now()]);
                    Notification::make()->success()->title('Contract sent to portal')->send();
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
