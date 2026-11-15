<?php

namespace App\Filament\Resources\CalculatorPricingResource\Pages;

use App\Filament\Resources\CalculatorPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorPricing extends EditRecord
{
    protected static string $resource = CalculatorPricingResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.calculator.pricing.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
