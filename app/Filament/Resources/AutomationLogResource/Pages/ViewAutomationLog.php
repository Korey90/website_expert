<?php

namespace App\Filament\Resources\AutomationLogResource\Pages;

use App\Filament\Resources\AutomationLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomationLog extends ViewRecord
{
    protected static string $resource = AutomationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
