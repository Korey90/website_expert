<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Actions\Finance\UpdateBankAccountAction;
use App\Filament\Resources\BankAccountResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.accounts.edit_subheading');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['account_number'] = null;
        $data['sort_code'] = null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateBankAccountAction::class)->execute($record, auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return BankAccountResource::getUrl('view', ['record' => $this->record]);
    }
}
