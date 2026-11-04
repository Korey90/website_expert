<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Actions\Finance\CreateExpenseAction;
use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateExpenseAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return ExpenseResource::getUrl('view', ['record' => $this->record]);
    }
}
