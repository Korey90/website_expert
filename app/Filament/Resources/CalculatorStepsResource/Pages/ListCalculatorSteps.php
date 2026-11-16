<?php

namespace App\Filament\Resources\CalculatorStepsResource\Pages;

use App\Filament\Resources\CalculatorStepsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCalculatorSteps extends ListRecords
{
    protected static string $resource = CalculatorStepsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
