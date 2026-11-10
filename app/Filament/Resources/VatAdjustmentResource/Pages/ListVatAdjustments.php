<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Filament\Resources\VatAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVatAdjustments extends ListRecords
{
    protected static string $resource = VatAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
