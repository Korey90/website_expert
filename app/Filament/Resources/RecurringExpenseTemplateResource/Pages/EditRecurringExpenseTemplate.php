<?php

namespace App\Filament\Resources\RecurringExpenseTemplateResource\Pages;

use App\Actions\Finance\UpdateRecurringExpenseTemplateAction;
use App\Filament\Resources\RecurringExpenseTemplateResource;
use App\Models\RecurringExpenseTemplateItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRecurringExpenseTemplate extends EditRecord
{
    protected static string $resource = RecurringExpenseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn (RecurringExpenseTemplateItem $item): array => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'tax_code_id' => $item->tax_code_id,
            'ledger_account_id' => $item->ledger_account_id,
            'category' => $item->category,
            'client_id' => $item->client_id,
            'project_id' => $item->project_id,
            'billable' => $item->billable,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateRecurringExpenseTemplateAction::class)->execute($record, auth()->user(), $data);
    }
}
