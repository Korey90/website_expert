<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Filament\Resources\VatReturnResource;
use Filament\Resources\Pages\ListRecords;

class ListVatReturns extends ListRecords
{
    protected static string $resource = VatReturnResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.vat_returns.list_subheading');
    }
}
