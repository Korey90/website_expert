<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Business\BusinessService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook'];
    private const ALLOWED_INTENTS = ['login', 'register'];

    /**
     * Redirect guest to the OAuth provider (new login / register flow).
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        $intent = $request->query('intent', 'login');

        if (! in_array($intent, self::ALLOWED_INTENTS, true)) {
            $intent = 'login';
        }

        $request->session()->put('social_auth_intent', $intent);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Redirect an already-authenticated user to the OAuth provider
     * to LINK an additional social account to their profile.
     */
    public function connect(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        // Mark in session so the callback knows this is a link operation
        $request->session()->put('social_linking', true);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback from the provider.
     * Handles two modes:
     *  a) social_linking=true in session  → link provider to currently logged-in user
     *  b) normal flow                     → login or register
     */
    public function callback(string $provider, Request $request, BusinessService $businessService): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        $intent = $request->session()->pull('social_auth_intent', 'login');

        if (! in_array($intent, self::ALLOWED_INTENTS, true)) {
            $intent = 'login';
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.social_login_failed')]);
        }

        // ── Mode A: Link to existing authenticated user ───────────────────
        if ($request->session()->pull('social_linking', false) && Auth::check()) {
            return $this->linkProviderToUser(Auth::user(), $provider, $socialUser);
        }

        // ── Mode B: Login / Register flow ─────────────────────────────────

        // Find existing social account
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_user_id', $socialUser->getId())
            ->with('user')
            ->first();

        if ($socialAccount) {
            // Update tokens
            $socialAccount->update([
                'provider_token'         => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken,
                'token_expires_at'       => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);

            Auth::login($socialAccount->user, remember: true);

            return redirect()->intended(route('portal.dashboard', absolute: false));
        }

        $user = User::where('email', $socialUser->getEmail())->first();
        $isNewUser = false;

        if (! $user) {
            if ($intent !== 'register') {
                return redirect()->route('login')
                    ->withErrors(['email' => __('auth.social_account_not_registered')]);
            }

            $user = new User([
                'email'             => $socialUser->getEmail(),
                'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'avatar_url'        => $socialUser->getAvatar(),
                'email_verified_at' => now(),
                'password'          => null,
            ]);

            $isNewUser = true;
        }

        if ($isNewUser) {
            $user->save();
            event(new Registered($user));
        } elseif (! $user->avatar_url && $socialUser->getAvatar()) {
            $user->update(['avatar_url' => $socialUser->getAvatar()]);
        }

        // Create the social account link
        $user->socialAccounts()->create([
            'provider'               => $provider,
            'provider_user_id'       => $socialUser->getId(),
            'provider_token'         => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
            'token_expires_at'       => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);

        // New users need a Business profile and client role
        if ($isNewUser) {
            $user->assignRole('client');

            Client::create([
                'company_name'          => $user->name,
                'primary_contact_name'  => $user->name,
                'primary_contact_email' => $user->email,
                'portal_user_id'        => $user->id,
                'status'                => 'prospect',
                'source'                => 'website',
            ]);

            $businessService->createForUser($user, ['name' => $user->name]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('portal.dashboard', absolute: false));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function linkProviderToUser(User $user, string $provider, mixed $socialUser): RedirectResponse
    {
        // Block if this provider is already linked to this account
        if ($user->socialAccounts()->where('provider', $provider)->exists()) {
            return redirect()->route('profile.edit')
                ->with('status', 'social-already-linked');
        }

        // Block if this provider ID is already linked to a DIFFERENT account
        if (SocialAccount::where('provider', $provider)
                ->where('provider_user_id', $socialUser->getId())
                ->exists()) {
            return redirect()->route('profile.edit')
                ->withErrors(['social' => __('auth.social_already_used')]);
        }

        $user->socialAccounts()->create([
            'provider'               => $provider,
            'provider_user_id'       => $socialUser->getId(),
            'provider_token'         => $socialUser->token,
            'provider_refresh_token' => $socialUser->refreshToken,
            'token_expires_at'       => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);

        return redirect()->route('profile.edit')
            ->with('status', 'social-linked');
    }
}

