<?php

namespace App\Policies;

use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class LandingPagePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->perm($user, 'view_landing_pages');
    }

    public function view(User $user, LandingPage $landingPage): bool
    {
        return $this->perm($user, 'view_landing_pages')
            && $this->isMemberOfBusiness($user, $landingPage);
    }

    public function create(User $user): bool
    {
        return $this->perm($user, 'manage_landing_pages');
    }

    public function generateAi(User $user): bool
    {
        return $this->perm($user, 'generate_landing_pages_ai')
            || $this->perm($user, 'manage_landing_pages');
    }

    public function update(User $user, LandingPage $landingPage): bool
    {
        return $this->perm($user, 'manage_landing_pages')
            && $this->isMemberOfBusiness($user, $landingPage);
    }

    public function delete(User $user, LandingPage $landingPage): bool
    {
        return $this->perm($user, 'manage_landing_pages')
            && $this->isMemberOfBusiness($user, $landingPage);
    }

    public function publish(User $user, LandingPage $landingPage): bool
    {
        return $this->perm($user, 'publish_landing_pages')
            && $this->isMemberOfBusiness($user, $landingPage);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Defensive permission check — returns false instead of throwing
     * PermissionDoesNotExist (e.g. on missing seeder / cache flush).
     */
    private function perm(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function isMemberOfBusiness(User $user, LandingPage $landingPage): bool
    {
        $business = currentBusiness();

        return $business && $business->id === $landingPage->business_id;
    }
}
