<?php

namespace App\Filament\Resources\VatAdjustmentResource\Pages;

use App\Actions\Finance\CreateVatAdjustmentAction;
use App\Filament\Resources\VatAdjustmentResource;
use App\Models\VatAdjustment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVatAdjustment extends CreateRecord
{
    protected static string $resource = VatAdjustmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateVatAdjustmentAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeCorrectionFields($data);
    }

    protected function getRedirectUrl(): string
    {
        return VatAdjustmentResource::getUrl('view', ['record' => $this->record]);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function normalizeCorrectionFields(array $data): array
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
}
