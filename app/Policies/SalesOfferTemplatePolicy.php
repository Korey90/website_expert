<?php

namespace App\Policies;

use App\Models\SalesOfferTemplate;
use App\Models\User;

class SalesOfferTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'developer', 'super_admin']);
    }

    public function view(User $user, SalesOfferTemplate $template): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasAnyRole(['admin', 'manager', 'developer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function update(User $user, SalesOfferTemplate $template): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, SalesOfferTemplate $template): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
