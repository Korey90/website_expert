<?php

namespace App\Filament\Resources\AccountingPeriodResource\Pages;

use App\Actions\Finance\CreateAccountingPeriodAction;
use App\Filament\Resources\AccountingPeriodResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccountingPeriod extends CreateRecord
{
    protected static string $resource = AccountingPeriodResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.periods.create_subheading');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAccountingPeriodAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }
}
