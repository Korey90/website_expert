<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_to_roles')
                ->label('Manage Roles')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->url(RoleResource::getUrl('index')),
            CreateAction::make()
                ->label('Add Permission')
                ->icon('heroicon-o-plus'),
        ];
    }
}
