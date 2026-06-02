<?php

namespace App\Notifications;

use App\Models\DomainOrder;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app Filament notification sent to all admins when domain registration fails.
 */
class DomainRegistrationFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DomainOrder $order,
        private readonly string      $reason,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $orderRoute = route(
            'filament.admin.resources.domain-orders.view',
            ['record' => $this->order->id]
        );

        $notification = FilamentNotification::make()
            ->title('❌ Domain Registration Failed')
            ->body("{$this->order->full_domain}: {$this->reason}")
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->actions([
                NotificationAction::make('view_order')
                    ->label('View Order')
                    ->url($orderRoute),
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
