<?php

namespace App\Filament\Resources\ContractTemplateResource\Pages;

use App\Filament\Resources\ContractTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractTemplates extends ListRecords
{
    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
