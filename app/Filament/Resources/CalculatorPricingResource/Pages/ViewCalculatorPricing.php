<?php

namespace App\Filament\Resources\CalculatorPricingResource\Pages;

use App\Filament\Resources\CalculatorPricingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCalculatorPricing extends ViewRecord
{
    protected static string $resource = CalculatorPricingResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.calculator.pricing.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
