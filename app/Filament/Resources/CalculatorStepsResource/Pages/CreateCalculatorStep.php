<?php

namespace App\Filament\Resources\CalculatorStepsResource\Pages;

use App\Filament\Resources\CalculatorStepsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalculatorStep extends CreateRecord
{
    protected static string $resource = CalculatorStepsResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.calculator.steps.create_subheading');
    }
}
