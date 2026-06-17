<?php

namespace App\Notifications;

use App\Models\DomainOrder;
use App\Services\Currency\MoneyFormatter;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\DatabaseNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app Filament notification sent to all admins/managers when a domain order is paid.
 */
class DomainOrderAdminNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DomainOrder $order) {}

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
        $price = app(MoneyFormatter::class)->format($this->order->retail_price, $this->order->currency);

        $notification = FilamentNotification::make()
            ->title('🌐 New Domain Order')
            ->body("{$this->order->full_domain} ({$this->order->action}, {$this->order->years}yr) — {$price}")
            ->icon('heroicon-o-globe-alt')
            ->iconColor('info')
            ->actions([
                NotificationAction::make('view_order')
                    ->label('View Order')
                    ->url($orderRoute),
            ]);

        $data = $notification->toArray();
        $data['format'] = 'filament';
        $data['duration'] = 'persistent';
        unset($data['id']);

        return $data;
    }

    public function databaseType(mixed $notifiable): string
    {
        return DatabaseNotification::class;
    }
}
