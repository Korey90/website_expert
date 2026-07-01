<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePasswordAction
{
    /**
     * Verify the current password and update to the new one.
     *
     * @param  array{current_password: string, password: string}  $data
     *
     * @throws ValidationException
     */
    public function execute(User $user, array $data): void
    {
        if ($user->password && ! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('account.password_incorrect'),
            ]);
        }

        $user->password = Hash::make($data['password']);
        $user->save();
    }
}
