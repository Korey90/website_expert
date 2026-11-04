<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Actions\Finance\UpdateExpenseAction;
use App\Filament\Resources\ExpenseResource;
use App\Models\ExpenseItem;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(fn (ExpenseItem $item): array => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'supply_date' => $item->supply_date?->toDateString(),
            'tax_code_id' => $item->tax_code_id,
            'ledger_account_id' => $item->ledger_account_id,
            'client_id' => $item->client_id,
            'project_id' => $item->project_id,
            'billable' => $item->billable,
        ])->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateExpenseAction::class)->execute($record, auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return ExpenseResource::getUrl('view', ['record' => $this->record]);
    }
}
