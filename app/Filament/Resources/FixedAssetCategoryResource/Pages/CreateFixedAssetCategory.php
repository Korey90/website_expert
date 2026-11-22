<?php

namespace App\Filament\Resources\FixedAssetCategoryResource\Pages;

use App\Actions\Finance\CreateFixedAssetCategoryAction;
use App\Filament\Resources\FixedAssetCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFixedAssetCategory extends CreateRecord
{
    protected static string $resource = FixedAssetCategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateFixedAssetCategoryAction::class)->execute(currentBusiness(), auth()->user(), $data);
    }
}
