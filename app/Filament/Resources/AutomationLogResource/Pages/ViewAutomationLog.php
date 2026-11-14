<?php

namespace App\Filament\Resources\AutomationLogResource\Pages;

use App\Filament\Resources\AutomationLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomationLog extends ViewRecord
{
    protected static string $resource = AutomationLogResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.logs.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
