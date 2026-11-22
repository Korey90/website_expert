<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Actions\Finance\CreateFixedAssetAction;
use App\Filament\Resources\FixedAssetResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFixedAsset extends CreateRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateFixedAssetAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return FixedAssetResource::getUrl('view', ['record' => $this->record]);
    }
}
