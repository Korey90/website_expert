<?php

namespace App\Filament\Resources\FixedAssetCategoryResource\Pages;

use App\Actions\Finance\UpdateFixedAssetCategoryAction;
use App\Filament\Resources\FixedAssetCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFixedAssetCategory extends EditRecord
{
    protected static string $resource = FixedAssetCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateFixedAssetCategoryAction::class)->execute($record, auth()->user(), $data);
    }
}
