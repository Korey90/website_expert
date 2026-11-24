<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\User;
use App\Support\PermissionHelper;
use Illuminate\Auth\Access\AuthorizationException;

final class AiAccess
{
    public static function allows(string $permission): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->is_active
            && PermissionHelper::allows($user, 'access_admin_panel')
            && PermissionHelper::allows($user, $permission)
            && currentBusiness()?->is_active === true;
    }

    public static function business(string $permission, ?string $expected = null): Business
    {
        if (! self::allows($permission)) {
            throw new AuthorizationException;
        }

        $business = currentBusiness();

        if (! $business || ($expected !== null && $business->id !== $expected)) {
            throw new AuthorizationException;
        }

        return $business;
    }
}
