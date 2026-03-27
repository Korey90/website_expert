<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_to_permissions')
                ->label('Manage Permissions')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->url(PermissionResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
