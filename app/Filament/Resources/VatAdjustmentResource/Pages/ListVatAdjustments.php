<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Filament\Resources\VatAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVatAdjustments extends ListRecords
{
    protected static string $resource = VatAdjustmentResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.vat_adjustments.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
