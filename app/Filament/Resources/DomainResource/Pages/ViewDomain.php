<?php

namespace App\Filament\Resources\DomainResource\Pages;

use App\Filament\Resources\DomainResource;
use App\Models\DomainEvent;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewDomain extends ViewRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->updateNameserversAction(),
            $this->toggleAutoRenewAction(),
            $this->sendRenewalReminderAction(),
        ];
    }

    private function updateNameserversAction(): Action
    {
        return Action::make('update_nameservers')
            ->label('Update Nameservers')
            ->icon('heroicon-o-server')
            ->color('gray')
            ->visible(fn () => $this->record->status === 'active')
            ->modalHeading('Update Nameservers')
            ->form([
                TagsInput::make('nameservers')
                    ->label('Nameservers')
                    ->placeholder('ns1.example.com')
                    ->default(fn () => $this->record->nameservers ?? [])
                    ->helperText('Add each nameserver and press Enter.'),
            ])
            ->modalSubmitActionLabel('Update Nameservers')
            ->action(function (array $data) {
                $domain = $this->record;
                $oldNameservers = $domain->nameservers ?? [];

                $domain->update(['nameservers' => $data['nameservers']]);

                DomainEvent::log(
                    domainId: $domain->id,
                    domainOrderId: null,
                    type: 'nameservers_updated',
                    description: 'Nameservers updated by admin.',
                    payload: [
                        'old' => $oldNameservers,
                        'new' => $data['nameservers'],
                    ],
                    userId: auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title('Nameservers updated')
                    ->send();

                $this->redirect(DomainResource::getUrl('view', ['record' => $this->record->id]));
            });
    }

    private function toggleAutoRenewAction(): Action
    {
        return Action::make('toggle_auto_renew')
            ->label(fn () => $this->record->auto_renew ? 'Disable Auto-Renew' : 'Enable Auto-Renew')
            ->icon(fn () => $this->record->auto_renew ? 'heroicon-o-arrow-path' : 'heroicon-o-arrow-path')
            ->color(fn () => $this->record->auto_renew ? 'warning' : 'success')
            ->visible(fn () => $this->record->status === 'active')
            ->requiresConfirmation()
            ->modalHeading(fn () => $this->record->auto_renew ? 'Disable Auto-Renew' : 'Enable Auto-Renew')
            ->modalDescription(fn () => $this->record->auto_renew
                ? 'Auto-renew will be disabled. The domain may expire without manual renewal.'
                : 'Auto-renew will be enabled. The domain will be renewed automatically before expiry.')
            ->modalSubmitActionLabel('Confirm')
            ->action(function () {
                $domain = $this->record;
                $newValue = ! $domain->auto_renew;

                $domain->update(['auto_renew' => $newValue]);

                DomainEvent::log(
                    domainId: $domain->id,
                    domainOrderId: null,
                    type: 'auto_renew_changed',
                    description: 'Auto-renew ' . ($newValue ? 'enabled' : 'disabled') . ' by admin.',
                    payload: ['auto_renew' => $newValue],
                    userId: auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title('Auto-renew ' . ($newValue ? 'enabled' : 'disabled'))
                    ->send();

                $this->redirect(DomainResource::getUrl('view', ['record' => $this->record->id]));
            });
    }

    private function sendRenewalReminderAction(): Action
    {
        return Action::make('send_renewal_reminder')
            ->label('Send Renewal Reminder')
            ->icon('heroicon-o-bell')
            ->color('warning')
            ->visible(fn () => $this->record->status === 'active' && $this->record->client_id !== null)
            ->requiresConfirmation()
            ->modalHeading('Send Renewal Reminder')
            ->modalDescription(fn () => "Send a manual renewal reminder email to the client for domain {$this->record->full_domain}.")
            ->modalSubmitActionLabel('Send Reminder')
            ->action(function () {
                $domain = $this->record;

                DomainEvent::log(
                    domainId: $domain->id,
                    domainOrderId: null,
                    type: 'renewal_reminder_sent',
                    description: 'Manual renewal reminder sent by admin.',
                    payload: ['sent_by' => auth()->id(), 'expires_at' => $domain->expires_at?->toDateString()],
                    userId: auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title('Renewal reminder logged')
                    ->body('Reminder has been logged. Automated email sending will be implemented in Sprint 5.')
                    ->send();

                $this->redirect(DomainResource::getUrl('view', ['record' => $this->record->id]));
            });
    }
}
