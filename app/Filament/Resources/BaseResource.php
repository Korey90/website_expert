<?php

namespace App\Filament\Resources;

use App\Filament\Support\FilamentPermissionRegistry;
use App\Support\PermissionHelper;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends FilamentResource
{
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::allowsAction('viewAny', fn (): bool => parent::canViewAny());
    }

    public static function canView(Model $record): bool
    {
        return static::allowsAction('view', fn (): bool => parent::canView($record));
    }

    public static function canCreate(): bool
    {
        return static::allowsAction('create', fn (): bool => parent::canCreate());
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowsAction('update', fn (): bool => parent::canEdit($record));
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowsAction('delete', fn (): bool => parent::canDelete($record));
    }

    public static function canDeleteAny(): bool
    {
        return static::allowsAction('deleteAny', fn (): bool => parent::canDeleteAny());
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::allowsAction('forceDelete', fn (): bool => parent::canForceDelete($record));
    }

    public static function canForceDeleteAny(): bool
    {
        return static::allowsAction('forceDeleteAny', fn (): bool => parent::canForceDeleteAny());
    }

    public static function canRestore(Model $record): bool
    {
        return static::allowsAction('restore', fn (): bool => parent::canRestore($record));
    }

    public static function canRestoreAny(): bool
    {
        return static::allowsAction('restoreAny', fn (): bool => parent::canRestoreAny());
    }

    public static function canReorder(): bool
    {
        return static::allowsAction('reorder', fn (): bool => parent::canReorder());
    }

    public static function canReplicate(Model $record): bool
    {
        return static::allowsAction('replicate', fn (): bool => parent::canReplicate($record));
    }

    protected static function allowsAction(string $action, callable $parentCheck): bool
    {
        $permission = FilamentPermissionRegistry::resourcePermission(static::class, $action);

        if ($permission && ! PermissionHelper::allows(auth()->user(), $permission)) {
            return false;
        }

        return $parentCheck();
    }
}