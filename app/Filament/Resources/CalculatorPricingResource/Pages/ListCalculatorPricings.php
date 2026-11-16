<?php namespace App\Filament\Resources\CalculatorPricingResource\Pages;
use App\Filament\Resources\CalculatorPricingResource;
use Filament\Actions; use Filament\Resources\Pages\ListRecords;
class ListCalculatorPricings extends ListRecords {
    protected static string $resource = CalculatorPricingResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
