<?php

namespace App\Policies;

use App\Models\SalesOfferTemplate;
use App\Models\User;
use App\Support\PermissionHelper;

class SalesOfferTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_sales_offer_templates');
    }

    public function view(User $user, SalesOfferTemplate $template): bool
    {
        return $this->canAccessTemplate($user, $template)
            && PermissionHelper::allows($user, 'view_sales_offer_templates');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_sales_offer_templates');
    }

    public function update(User $user, SalesOfferTemplate $template): bool
    {
        return $this->canModifyTemplate($user, $template)
            && PermissionHelper::allows($user, 'edit_sales_offer_templates');
    }

    public function delete(User $user, SalesOfferTemplate $template): bool
    {
        return $this->canModifyTemplate($user, $template)
            && PermissionHelper::allows($user, 'delete_sales_offer_templates');
    }

    private function canAccessTemplate(User $user, SalesOfferTemplate $template): bool
    {
        return $template->business_id === null
            || $template->business_id === currentBusiness()?->id
            || PermissionHelper::allows($user, 'view_businesses');
    }

    private function canModifyTemplate(User $user, SalesOfferTemplate $template): bool
    {
        return $template->business_id === currentBusiness()?->id
            || PermissionHelper::allows($user, 'view_businesses');
    }
}
