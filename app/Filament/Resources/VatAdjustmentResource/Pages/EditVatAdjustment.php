<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Actions\Finance\UpdateVatAdjustmentAction;
use App\Filament\Resources\VatAdjustmentResource;
use App\Models\VatAdjustment;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVatAdjustment extends EditRecord
{
    protected static string $resource = VatAdjustmentResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.vat_adjustments.edit_subheading');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateVatAdjustmentAction::class)->execute($record, auth()->user(), $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['adjustment_type'] ?? null) === VatAdjustment::TYPE_ERROR_CORRECTION) {
            return $data;
        }

        return [
            ...$data,
            'correction_method' => VatAdjustment::METHOD_NOT_ERROR,
            'discovered_on' => null,
            'original_period_from' => null,
            'original_period_to' => null,
            'is_deliberate' => false,
            'hmrc_reference' => null,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return VatAdjustmentResource::getUrl('view', ['record' => $this->record]);
    }
}
