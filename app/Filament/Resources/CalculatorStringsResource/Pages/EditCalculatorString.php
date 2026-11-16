<?php

namespace App\Filament\Resources\CalculatorStringsResource\Pages;

use App\Filament\Resources\CalculatorStringsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorString extends EditRecord
{
    protected static string $resource = CalculatorStringsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
