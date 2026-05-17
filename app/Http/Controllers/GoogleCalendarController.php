<?php

namespace App\Http\Controllers;

use App\Services\Calendar\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleCalendarController extends Controller
{
    private const SCOPES = [
        'https://www.googleapis.com/auth/calendar',
        'email',
        'profile',
    ];

    public function __construct(
        private readonly GoogleCalendarService $googleCalendarService,
    ) {}

    /**
     * Redirect user to Google OAuth consent screen (calendar scope).
     */
    public function connect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(self::SCOPES)
            ->with([
                'access_type'    => 'offline',
                'prompt'         => 'consent',
                'redirect_uri'   => route('admin.google-calendar.callback'),
            ])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback and store token.
     */
    public function callback(): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')
                ->with(['redirect_uri' => route('admin.google-calendar.callback')])
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('filament.admin.pages.calendar-page')
                ->with('error', 'Google authorization failed: ' . $e->getMessage());
        }

        $expiresIn = $socialUser->expiresIn ?? 3600;

        $this->googleCalendarService->saveToken(
            userId:       Auth::id(),
            businessId:   currentBusiness()?->id,
            accessToken:  $socialUser->token,
            refreshToken: $socialUser->refreshToken,
            expiresIn:    (int) $expiresIn,
        );

        return redirect()->route('filament.admin.pages.calendar-page')
            ->with('success', 'Google Calendar connected successfully.');
    }

    /**
     * Remove stored Google Calendar token for the current user/business.
     */
    public function disconnect(): RedirectResponse
    {
        $this->googleCalendarService->disconnect(Auth::id(), currentBusiness()?->id);

        return redirect()->route('filament.admin.pages.calendar-page')
            ->with('success', 'Google Calendar disconnected.');
    }
}
