<?php

namespace App\Filament\Resources\SalesOfferTemplateResource\Pages;

use App\Filament\Resources\SalesOfferTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOfferTemplate extends ViewRecord
{
    protected static string $resource = SalesOfferTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
