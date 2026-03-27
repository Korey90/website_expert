<?php

namespace App\Livewire;

use Filament\Notifications\Livewire\DatabaseNotifications;
use Livewire\Attributes\On;

class CustomDatabaseNotifications extends DatabaseNotifications
{
    /**
     * Override the parent's removeNotification WITHOUT #[On('notificationClosed')].
     * This prevents Livewire from binding "notificationClosed" to a DELETE — the
     * parent has #[On('notificationClosed')] on this method; by overriding it here
     * without the attribute, PHP reflection on the child class won't see that attribute,
     * so Livewire won't auto-fire a delete when a notification is dismissed.
     *
     * Our JS X-button handler calls this explicitly via wire.removeNotification(id).
     */
    public function removeNotification(string $id): void
    {
        $this->getNotificationsQuery()
            ->where('id', $id)
            ->delete();
    }

    /**
     * When Alpine dismisses a notification (X animation ends → notificationClosed event),
     * only mark it as read — keep it in the database.
     */
    #[On('notificationClosed')]
    public function onNotificationClosed(string $id): void
    {
        $this->getNotificationsQuery()
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
