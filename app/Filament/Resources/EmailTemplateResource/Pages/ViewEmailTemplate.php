<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailTemplate extends ViewRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
