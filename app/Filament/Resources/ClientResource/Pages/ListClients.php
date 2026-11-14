<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.pilot.clients.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
