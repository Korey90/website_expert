<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Resources\Pages\EditRecord;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.landing_pages.edit_subheading');
    }
}
