<?php

namespace App\Filament\Resources\ServiceItemResource\Pages;

use App\Filament\Resources\ServiceItemResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceItems extends ListRecords
{
    protected static string $resource = ServiceItemResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.services.list_subheading');
    }
}
