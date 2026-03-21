<?php

namespace App\Filament\Resources\SiteSectionResource\Pages;

use App\Filament\Resources\SiteSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSiteSection extends ViewRecord
{
    protected static string $resource = SiteSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
