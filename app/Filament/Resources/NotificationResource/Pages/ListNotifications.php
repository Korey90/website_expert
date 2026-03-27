<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Send Notification')
                ->icon('heroicon-o-paper-airplane'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->modifyQueryUsing(fn (Builder $query) => $query),
            'unread' => Tab::make('Unread')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('read_at')->whereNull('deleted_at'))
                ->badge(fn () => \App\Models\DatabaseNotification::withoutGlobalScopes()->where('data->format', 'filament')->whereNull('read_at')->whereNull('deleted_at')->count()),
            'read' => Tab::make('Read')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('read_at')->whereNull('deleted_at')),
            'dismissed' => Tab::make('Dismissed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('deleted_at')),
        ];
    }
}
