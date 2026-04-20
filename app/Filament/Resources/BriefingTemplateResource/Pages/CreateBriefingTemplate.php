<?php

namespace App\Filament\Resources\BriefingTemplateResource\Pages;

use App\Filament\Resources\BriefingTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBriefingTemplate extends CreateRecord
{
    protected static string $resource = BriefingTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['business_id'] = currentBusiness()?->id;

        return $data;
    }
}
