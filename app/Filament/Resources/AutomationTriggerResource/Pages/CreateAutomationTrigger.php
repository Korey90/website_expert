<?php

namespace App\Filament\Resources\AutomationTriggerResource\Pages;

use App\Filament\Resources\AutomationTriggerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomationTrigger extends CreateRecord
{
    protected static string $resource = AutomationTriggerResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.triggers.create_subheading');
    }
}
