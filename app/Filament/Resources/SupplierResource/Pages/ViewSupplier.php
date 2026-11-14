<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.procurement.suppliers.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
