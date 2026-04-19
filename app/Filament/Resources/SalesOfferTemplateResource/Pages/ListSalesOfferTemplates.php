<?php

namespace App\Filament\Resources\SalesOfferTemplateResource\Pages;

use App\Filament\Resources\SalesOfferTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesOfferTemplates extends ListRecords
{
    protected static string $resource = SalesOfferTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
