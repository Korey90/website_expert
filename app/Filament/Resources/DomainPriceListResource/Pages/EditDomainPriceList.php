<?php

namespace App\Filament\Resources\DomainPriceListResource\Pages;

use App\Filament\Resources\DomainPriceListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDomainPriceList extends EditRecord
{
    protected static string $resource = DomainPriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
