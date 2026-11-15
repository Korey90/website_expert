<?php

namespace App\Filament\Resources\CalculatorStringsResource\Pages;

use App\Filament\Resources\CalculatorStringsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalculatorString extends CreateRecord
{
    protected static string $resource = CalculatorStringsResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.calculator.strings.create_subheading');
    }
}
