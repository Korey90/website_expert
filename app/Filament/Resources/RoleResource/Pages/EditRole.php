<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * Pre-populate grouped perms_* fields from the role's current permissions.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $current  = $this->record->permissions->pluck('name')->all();
        $groups   = RoleResource::permissionGroups();

        foreach ($groups as $key => $group) {
            $data["perms_{$key}"] = array_values(array_filter($current, function ($name) use ($group) {
                foreach ($group['suffixes'] as $suffix) {
                    if (str_ends_with($name, '_' . $suffix) || $name === $suffix) {
                        return true;
                    }
                }
                return false;
            }));
        }

        return $data;
    }

    /**
     * Strip perms_* keys so Eloquent doesn't try to fill unknown columns.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'perms_')) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Sync selected permissions after the role record is saved.
     */
    protected function afterSave(): void
    {
        $all = [];
        foreach (array_keys(RoleResource::permissionGroups()) as $key) {
            $all = array_merge($all, $this->data["perms_{$key}"] ?? []);
        }
        $this->record->syncPermissions($all);
    }
}
