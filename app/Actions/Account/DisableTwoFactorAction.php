<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class DisableTwoFactorAction
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Verify the TOTP code and disable 2FA for the user.
     *
     * @throws ValidationException
     */
    public function execute(User $user, string $code): void
    {
        if (! $user->google_2fa_secret) {
            throw ValidationException::withMessages([
                'disable_totp_code' => __('account.2fa_secret_missing'),
            ]);
        }

        $valid = $this->google2fa->verifyKey($user->google_2fa_secret, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'disable_totp_code' => __('account.2fa_code_invalid'),
            ]);
        }

        $user->two_factor_enabled = false;
        $user->google_2fa_secret = null;
        $user->save();
    }
}
