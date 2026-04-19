<?php

namespace App\Policies;

use App\Models\SalesOffer;
use App\Models\User;

class SalesOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function view(User $user, SalesOffer $offer): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasAnyRole(['admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function update(User $user, SalesOffer $offer): bool
    {
        if (! $offer->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function delete(User $user, SalesOffer $offer): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function send(User $user, SalesOffer $offer): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin'])
            && $offer->isEditable();
    }
}
