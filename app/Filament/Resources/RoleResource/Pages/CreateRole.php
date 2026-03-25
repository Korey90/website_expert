<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * Strip perms_* keys so Eloquent doesn't try to fill unknown columns.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'perms_')) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Sync selected permissions after the role record is created.
     */
    protected function afterCreate(): void
    {
        $all = [];
        foreach (array_keys(RoleResource::permissionGroups()) as $key) {
            $all = array_merge($all, $this->data["perms_{$key}"] ?? []);
        }
        $this->record->syncPermissions($all);
    }
}
