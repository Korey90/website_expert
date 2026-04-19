<?php

namespace App\Filament\Resources\BriefingTemplateResource\Pages;

use App\Filament\Resources\BriefingTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBriefingTemplate extends EditRecord
{
    protected static string $resource = BriefingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
