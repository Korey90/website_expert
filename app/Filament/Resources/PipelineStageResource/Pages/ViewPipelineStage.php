<?php

namespace App\Filament\Resources\PipelineStageResource\Pages;

use App\Filament\Resources\PipelineStageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPipelineStage extends ViewRecord
{
    protected static string $resource = PipelineStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
