<?php

namespace App\Filament\Resources\SmsTemplateResource\Pages;

use App\Filament\Resources\SmsTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmsTemplate extends EditRecord
{
    protected static string $resource = SmsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
