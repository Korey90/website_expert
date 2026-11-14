<?php

namespace App\Filament\Resources\AccountingPeriodResource\Pages;

use App\Actions\Finance\UpdateAccountingPeriodAction;
use App\Filament\Resources\AccountingPeriodResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAccountingPeriod extends EditRecord
{
    protected static string $resource = AccountingPeriodResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.periods.edit_subheading');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateAccountingPeriodAction::class)->execute($record, auth()->user(), $data);
    }
}
