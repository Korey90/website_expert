<?php

namespace App\Filament\Resources\CalculatorStringsResource\Pages;

use App\Filament\Resources\CalculatorStringsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalculatorStrings extends ListRecords
{
    protected static string $resource = CalculatorStringsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
