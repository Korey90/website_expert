<?php

namespace App\Policies;

use App\Models\BriefingTemplate;
use App\Models\User;
use App\Support\PermissionHelper;

class BriefingTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_briefing_templates');
    }

    public function view(User $user, BriefingTemplate $template): bool
    {
        return PermissionHelper::allows($user, 'view_briefing_templates');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_briefing_templates');
    }

    public function update(User $user, BriefingTemplate $template): bool
    {
        return PermissionHelper::allows($user, 'edit_briefing_templates');
    }

    public function delete(User $user, BriefingTemplate $template): bool
    {
        return PermissionHelper::allows($user, 'delete_briefing_templates');
    }
}
