<?php

namespace App\Filament\Resources\SiteSectionResource\Pages;

use App\Filament\Resources\SiteSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteSections extends ListRecords
{
    protected static string $resource = SiteSectionResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.sections.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
