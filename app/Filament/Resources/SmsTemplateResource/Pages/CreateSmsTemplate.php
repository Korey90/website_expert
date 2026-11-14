<?php

namespace App\Filament\Resources\SmsTemplateResource\Pages;

use App\Filament\Resources\SmsTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSmsTemplate extends CreateRecord
{
    protected static string $resource = SmsTemplateResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.sms_templates.create_subheading');
    }
}
