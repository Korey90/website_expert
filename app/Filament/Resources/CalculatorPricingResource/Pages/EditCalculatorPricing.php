<?php namespace App\Filament\Resources\CalculatorPricingResource\Pages;
use App\Filament\Resources\CalculatorPricingResource;
use Filament\Actions; use Filament\Resources\Pages\EditRecord;
class EditCalculatorPricing extends EditRecord {
    protected static string $resource = CalculatorPricingResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
