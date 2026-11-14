<?php

namespace App\Filament\Resources\AutomationTriggerResource\Pages;

use App\Filament\Resources\AutomationTriggerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomationTrigger extends EditRecord
{
    protected static string $resource = AutomationTriggerResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.triggers.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->is_system),
        ];
    }
}
