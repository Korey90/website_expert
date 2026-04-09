<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook'];

    /**
     * Unlink (remove) a social account from the authenticated user.
     *
     * Guard rule: cannot unlink if this is the user's only authentication method
     * (i.e. no password set and no other social accounts linked).
     */
    public function destroy(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        $user = $request->user();

        $socialAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->firstOrFail();

        // Safety guard: at least one auth method must remain
        $remainingSocialCount = $user->socialAccounts()
            ->where('provider', '!=', $provider)
            ->count();

        if (is_null($user->password) && $remainingSocialCount === 0) {
            return back()->withErrors([
                'social' => __('auth.cannot_unlink_last_social'),
            ]);
        }

        $socialAccount->delete();

        return back()->with('status', 'social-unlinked');
    }
}
