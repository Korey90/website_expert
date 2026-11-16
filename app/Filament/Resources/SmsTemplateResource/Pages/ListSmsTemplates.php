<?php

namespace App\Filament\Resources\SmsTemplateResource\Pages;

use App\Filament\Resources\SmsTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmsTemplates extends ListRecords
{
    protected static string $resource = SmsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
