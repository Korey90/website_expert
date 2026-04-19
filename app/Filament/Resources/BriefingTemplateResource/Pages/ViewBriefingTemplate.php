<?php

namespace App\Filament\Resources\BriefingTemplateResource\Pages;

use App\Filament\Resources\BriefingTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBriefingTemplate extends ViewRecord
{
    protected static string $resource = BriefingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
