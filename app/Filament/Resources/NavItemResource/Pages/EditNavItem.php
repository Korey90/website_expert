<?php

namespace App\Filament\Resources\NavItemResource\Pages;

use App\Filament\Resources\NavItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNavItem extends EditRecord
{
    protected static string $resource = NavItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['label'] = $record->getTranslations('label');

        return $data;
    }
}
