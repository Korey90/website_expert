<?php

namespace App\Policies;

use App\Models\SalesOffer;
use App\Models\User;
use App\Support\PermissionHelper;

class SalesOfferPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_sales_offers');
    }

    public function view(User $user, SalesOffer $offer): bool
    {
        return PermissionHelper::allows($user, 'view_sales_offers');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_sales_offers');
    }

    public function update(User $user, SalesOffer $offer): bool
    {
        if (! $offer->isEditable()) {
            return false;
        }

        return PermissionHelper::allows($user, 'edit_sales_offers');
    }

    public function delete(User $user, SalesOffer $offer): bool
    {
        return PermissionHelper::allows($user, 'delete_sales_offers');
    }

    public function send(User $user, SalesOffer $offer): bool
    {
        return PermissionHelper::allows($user, 'send_sales_offers')
            && $offer->isEditable();
    }
}
