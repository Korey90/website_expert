<?php

namespace App\Filament\Resources\CalculatorStepsResource\Pages;

use App\Filament\Resources\CalculatorStepsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorStep extends EditRecord
{
    protected static string $resource = CalculatorStepsResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.calculator.steps.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
