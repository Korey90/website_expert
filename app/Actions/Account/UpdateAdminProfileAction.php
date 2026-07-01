<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UpdateAdminProfileAction
{
    /**
     * Update the admin user's personal profile data.
     *
     * @param  array{name: string, email: string, phone: ?string, locale: ?string, avatar: ?string}  $data
     */
    public function execute(User $user, array $data): void
    {
        if (isset($data['avatar']) && $data['avatar']) {
            // Delete old avatar if it exists and is stored locally
            if ($user->avatar_url && str_starts_with($user->avatar_url, 'avatars/')) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            $user->avatar_url = $data['avatar'];
        }

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        $user->locale = $data['locale'] ?? 'en';

        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
