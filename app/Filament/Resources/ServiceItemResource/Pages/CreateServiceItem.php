<?php

namespace App\Filament\Resources\ServiceItemResource\Pages;

use App\Filament\Resources\ServiceItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceItem extends CreateRecord
{
    protected static string $resource = ServiceItemResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.services.create_subheading');
    }
}
