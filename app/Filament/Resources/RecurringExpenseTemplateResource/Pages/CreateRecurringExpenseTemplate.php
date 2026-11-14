<?php

namespace App\Filament\Resources\RecurringExpenseTemplateResource\Pages;

use App\Actions\Finance\CreateRecurringExpenseTemplateAction;
use App\Filament\Resources\RecurringExpenseTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRecurringExpenseTemplate extends CreateRecord
{
    protected static string $resource = RecurringExpenseTemplateResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.procurement.recurring_expenses.create_subheading');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateRecurringExpenseTemplateAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return RecurringExpenseTemplateResource::getUrl('edit', ['record' => $this->record]);
    }
}
