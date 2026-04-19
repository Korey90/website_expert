<?php

namespace App\Notifications;

use App\Models\SalesOffer;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * In-app Filament notification when a client clicks CTA on a sales offer.
 * Uses Filament notification format so it appears in the bell widget.
 */
class SalesOfferCtaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SalesOffer $offer) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $client    = $this->offer->lead?->client;
        $name      = $client?->company_name ?? $client?->primary_contact_name ?? 'Unknown';
        $leadRoute = route('filament.admin.resources.leads.view', ['record' => $this->offer->lead_id]);
        $notifId   = (string) Str::orderedUuid();

        $notification = FilamentNotification::make()
            ->title("🔥 Klient zainteresowany ofertą")
            ->body("{$name} kliknął CTA w ofercie: {$this->offer->title}")
            ->icon('heroicon-o-cursor-arrow-rays')
            ->iconColor('success')
            ->actions([
                NotificationAction::make('view')
                    ->label('Zobacz leada')
                    ->url($leadRoute),
            ]);

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
