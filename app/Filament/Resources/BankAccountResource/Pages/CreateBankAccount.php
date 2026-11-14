<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Actions\Finance\CreateBankAccountAction;
use App\Filament\Resources\BankAccountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBankAccount extends CreateRecord
{
    protected static string $resource = BankAccountResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.accounts.create_subheading');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateBankAccountAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return BankAccountResource::getUrl('view', ['record' => $this->record]);
    }
}
