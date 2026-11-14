<?php

namespace App\Filament\Resources\SiteSectionResource\Pages;

use App\Filament\Resources\SiteSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSiteSection extends CreateRecord
{
    protected static string $resource = SiteSectionResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.sections.create_subheading');
    }
}
