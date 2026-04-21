<?php

namespace App\Filament\Resources\SiteSectionResource\Pages;

use App\Filament\Resources\SiteSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteSection extends EditRecord
{
    protected static string $resource = SiteSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach (['title', 'subtitle', 'body', 'button_text'] as $field) {
            $data[$field] = $record->getTranslations($field);
        }

        // Expose nested extra keys so Repeaters can bind to extra.highlights / extra.stats
        $extra = $record->extra ?? [];
        $data['extra'] = $extra;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $existing  = $record->extra ?? [];
        $submitted = $data['extra'] ?? [];

        // Merge: submitted wins for keys it provides; existing preserved for keys
        // the current form view does not include (hidden sections for other section types).
        $data['extra'] = array_merge($existing, $submitted);

        return $data;
    }
}
