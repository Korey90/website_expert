<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Str;

class NotifyAdminAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $vars  = array_merge($context, $this->buildTemplateVars($context));
        $title = $this->interpolate($action['title'] ?? 'Admin Notification', $vars);
        $body  = $this->interpolate($action['body']  ?? 'Event: ' . $triggerEvent, $vars);
        $url   = $this->interpolate($action['url']   ?? '', $vars);
        $icon  = $action['icon']  ?? 'heroicon-o-bell';
        $color = $action['color'] ?? 'primary';
        $roles = $action['roles'] ?? ['admin'];

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', (array) $roles))->get();

        if ($users->isEmpty()) {
            throw new ActionSkippedException("No users found with roles: " . implode(', ', (array) $roles));
        }

        foreach ($users as $user) {
            $notifId   = (string) Str::orderedUuid();
            $followUrl = $url ? route('notification.follow', ['to' => $url, 'id' => $notifId]) : null;

            $notification = FilamentNotification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->iconColor($color);

            if ($followUrl) {
                $notification->actions([
                    NotificationAction::make('view')
                        ->label('View')
                        ->url($followUrl),
                ]);
            }

            $data             = $notification->toArray();
            $data['format']   = 'filament';
            $data['duration'] = 'persistent';
            unset($data['id']);

            $user->notifications()->create([
                'id'      => $notifId,
                'type'    => \Filament\Notifications\DatabaseNotification::class,
                'data'    => $data,
                'read_at' => null,
            ]);

            \Filament\Notifications\Events\DatabaseNotificationsSent::dispatch($user);
        }
    }
}
