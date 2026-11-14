<?php

namespace App\Filament\Resources\AutomationTriggerResource\Pages;

use App\Filament\Resources\AutomationTriggerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomationTrigger extends ViewRecord
{
    protected static string $resource = AutomationTriggerResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.triggers.view_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->is_system),
        ];
    }
}
