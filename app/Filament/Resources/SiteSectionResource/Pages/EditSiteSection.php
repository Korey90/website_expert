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

        $extra = $record->extra ?? [];
        $data['extra'] = $extra;

        // Feed extra_kv for sections that use the KeyValue fallback panel.
        // KeyValue registers KeyValueStateCast which would corrupt data.extra
        // if we used KeyValue::make('extra') directly. Using a separate path
        // extra_kv avoids the collision entirely.
        $data['extra_kv'] = $extra;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record    = $this->getRecord();
        $existing  = $record->extra ?? [];
        $submitted = $data['extra'] ?? [];

        // For sections using the KeyValue fallback panel, extra_kv holds
        // the edited key-value pairs (already cast back to associative by
        // KeyValueStateCast::get() during dehydration).
        $extraKv = $data['extra_kv'] ?? null;
        if (is_array($extraKv) && ! empty($extraKv)) {
            $submitted = array_merge($submitted, $extraKv);
        }

        // Merge: submitted wins; existing keys not present in submitted are kept.
        $data['extra'] = array_merge($existing, $submitted);

        // Remove virtual field — not a real model attribute.
        unset($data['extra_kv']);

        return $data;
    }
}
