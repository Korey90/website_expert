<?php

namespace App\Filament\Resources\NavItemResource\Pages;

use App\Filament\Resources\NavItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNavItem extends CreateRecord
{
    protected static string $resource = NavItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure label is stored as proper JSON, not as dot-notation array
        if (isset($data['label']) && is_array($data['label'])) {
            $data['label'] = array_filter($data['label'], fn ($v) => $v !== null && $v !== '');
        }

        return $data;
    }
}
