<?php

namespace App\Notifications;

use App\Models\Quote;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuoteAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Quote $quote) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $client    = $this->quote->client;
        $name      = $client?->company_name ?? $client?->primary_contact_name ?? 'Unknown';
        $leadRoute = $this->quote->lead_id
            ? route('filament.admin.resources.leads.view', ['record' => $this->quote->lead_id])
            : null;

        $notification = FilamentNotification::make()
            ->title('✅ Wycena zaakceptowana')
            ->body("{$name} zaakceptował wycenę {$this->quote->number}")
            ->icon('heroicon-o-check-circle')
            ->iconColor('success');

        if ($leadRoute) {
            $notification->actions([
                NotificationAction::make('view_lead')
                    ->label('Zobacz leada')
                    ->url($leadRoute),
            ]);
        }

        $data             = $notification->toArray();
        $data['format']   = 'filament';
        $data['duration'] = 'persistent';
        unset($data['id']);

        return $data;
    }

    public function databaseType(mixed $notifiable): string
    {
        return \Filament\Notifications\DatabaseNotification::class;
    }
}
