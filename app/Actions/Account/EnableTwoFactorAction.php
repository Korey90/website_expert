<?php

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FAQRCode\Google2FA;

class EnableTwoFactorAction
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Generate a new TOTP secret and store it (unconfirmed).
     * Returns the QR code SVG for display.
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        // Store temporarily — not yet enabled until confirmed
        $user->google_2fa_secret = $secret;
        $user->two_factor_enabled = false;
        $user->save();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        $qrSvg = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            $secret,
        );

        return [
            'secret' => $secret,
            'qr_svg' => $qrSvg,
            'qr_url' => $qrCodeUrl,
        ];
    }

    /**
     * Confirm the TOTP code and activate 2FA.
     *
     * @throws ValidationException
     */
    public function confirm(User $user, string $code): void
    {
        if (! $user->google_2fa_secret) {
            throw ValidationException::withMessages([
                'totp_code' => __('account.2fa_secret_missing'),
            ]);
        }

        $valid = $this->google2fa->verifyKey($user->google_2fa_secret, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'totp_code' => __('account.2fa_code_invalid'),
            ]);
        }

        $user->two_factor_enabled = true;
        $user->save();
    }
}
