<?php

namespace App\Filament\Resources\ContractTemplateResource\Pages;

use App\Filament\Resources\ContractTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditContractTemplate extends EditRecord
{
    protected static string $resource = ContractTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
