<?php

namespace App\Filament\Resources\SalesOfferTemplateResource\Pages;

use App\Filament\Resources\SalesOfferTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOfferTemplate extends CreateRecord
{
    protected static string $resource = SalesOfferTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = currentBusiness()?->id;

        return $data;
    }
}
