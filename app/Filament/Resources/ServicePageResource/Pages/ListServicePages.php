<?php

namespace App\Filament\Resources\ServicePageResource\Pages;

use App\Filament\Resources\ServicePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServicePages extends ListRecords
{
    protected static string $resource = ServicePageResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.service_pages.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
