<?php

namespace App\Filament\Pages;

use App\Filament\Support\FilamentPermissionRegistry;
use App\Support\PermissionHelper;
use Filament\Pages\Page as FilamentPage;

abstract class BasePage extends FilamentPage
{
    public static function canAccess(): bool
    {
        $permission = FilamentPermissionRegistry::pagePermission(static::class);

        return filled($permission)
            && PermissionHelper::allows(auth()->user(), $permission)
            && parent::canAccess();
    }
}
