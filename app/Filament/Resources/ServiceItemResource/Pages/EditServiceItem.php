<?php

namespace App\Filament\Resources\ServiceItemResource\Pages;

use App\Filament\Resources\ServiceItemResource;
use Filament\Resources\Pages\EditRecord;

class EditServiceItem extends EditRecord
{
    protected static string $resource = ServiceItemResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.services.edit_subheading');
    }
}
