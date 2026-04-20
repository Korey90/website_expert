<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class PermissionHelper
{
    public static function allows(?Authenticatable $user, string $permission): bool
    {
        if (! $user || ! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}