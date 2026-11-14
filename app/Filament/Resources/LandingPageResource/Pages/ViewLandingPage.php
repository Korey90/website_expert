<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLandingPage extends ViewRecord
{
    protected static string $resource = LandingPageResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.landing_pages.view_subheading');
    }
}
