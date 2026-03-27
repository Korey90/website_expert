<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use App\Models\DatabaseNotification;
use App\Models\User;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $userIds = $data['user_ids'] ?? [];
        $title   = $data['title'];
        $body    = $data['body'] ?? '';

        $lastNotif = null;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $notifId   = (string) Str::orderedUuid();
            $notifData = [
                'title'    => $title,
                'body'     => $body,
                'icon'     => 'heroicon-o-bell',
                'iconColor' => 'primary',
                'duration' => 'persistent',
                'format'   => 'filament',
            ];

            $lastNotif = $user->notifications()->create([
                'id'      => $notifId,
                'type'    => \Filament\Notifications\DatabaseNotification::class,
                'data'    => $notifData,
                'read_at' => null,
            ]);

            DatabaseNotificationsSent::dispatch($user);
        }

        return $lastNotif ?? new DatabaseNotification();
    }
}
