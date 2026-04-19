<?php

namespace App\Filament\Resources\BriefingTemplateResource\Pages;

use App\Filament\Resources\BriefingTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBriefingTemplates extends ListRecords
{
    protected static string $resource = BriefingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
