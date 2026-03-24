<?php

namespace App\Filament\Resources\ContractTemplateResource\Pages;

use App\Filament\Resources\ContractTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContractTemplate extends ViewRecord
{
    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
