<?php

namespace App\Filament\Resources\DomainOrderResource\Pages;

use App\Filament\Resources\DomainOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListDomainOrders extends ListRecords
{
    protected static string $resource = DomainOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
