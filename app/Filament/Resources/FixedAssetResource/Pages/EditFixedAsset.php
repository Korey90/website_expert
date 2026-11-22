<?php

namespace App\Filament\Resources\FixedAssetResource\Pages;

use App\Actions\Finance\UpdateFixedAssetAction;
use App\Filament\Resources\FixedAssetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFixedAsset extends EditRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateFixedAssetAction::class)->execute($record, auth()->user(), $data);
    }
}
