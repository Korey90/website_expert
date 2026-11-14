<?php

namespace App\Filament\Resources\BusinessResource\Pages;

use App\Filament\Resources\BusinessResource;
use Filament\Resources\Pages\ListRecords;

class ListBusinesses extends ListRecords
{
    protected static string $resource = BusinessResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.businesses.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
