<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Handles communication with the Google Calendar REST API.
 * Uses stored OAuth tokens (GoogleCalendarToken) per user/business.
 */
class GoogleCalendarService
{
    private const API_BASE = 'https://www.googleapis.com/calendar/v3';

    // ── Token management ──────────────────────────────────────────────────

    public function getToken(int $userId, ?string $businessId): ?GoogleCalendarToken
    {
        return GoogleCalendarToken::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->first();
    }

    public function isConnected(int $userId, ?string $businessId): bool
    {
        return $this->getToken($userId, $businessId) !== null;
    }

    /**
     * Store or update token from Socialite OAuth callback.
     */
    public function saveToken(
        int $userId,
        ?string $businessId,
        string $accessToken,
        ?string $refreshToken,
        int $expiresIn,
        string $calendarId = 'primary',
    ): GoogleCalendarToken {
        return GoogleCalendarToken::updateOrCreate(
            ['user_id' => $userId, 'business_id' => $businessId],
            [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at'    => Carbon::now()->addSeconds($expiresIn),
                'calendar_id'   => $calendarId,
            ]
        );
    }

    public function disconnect(int $userId, ?string $businessId): void
    {
        GoogleCalendarToken::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->delete();
    }

    /**
     * Refresh access token using stored refresh_token.
     */
    public function refreshAccessToken(GoogleCalendarToken $token): bool
    {
        if (! $token->refresh_token) {
            return false;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id'     => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $token->refresh_token,
            'grant_type'    => 'refresh_token',
        ]);

        if (! $response->successful()) {
            Log::warning('GoogleCalendar: token refresh failed', [
                'user_id'     => $token->user_id,
                'business_id' => $token->business_id,
                'response'    => $response->json(),
            ]);
            return false;
        }

        $data = $response->json();
        $token->update([
            'access_token' => $data['access_token'],
            'expires_at'   => Carbon::now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return true;
    }

    // ── Calendar API calls ────────────────────────────────────────────────

    /**
     * Push a CalendarEvent to Google Calendar.
     * Returns Google event ID on success, null on failure.
     */
    public function pushEvent(CalendarEvent $event, int $userId, ?string $businessId): ?string
    {
        $token = $this->getToken($userId, $businessId);
        if (! $token) {
            return null;
        }

        if ($token->isExpired() && ! $this->refreshAccessToken($token)) {
            return null;
        }

        $calendarId = $token->calendar_id;
        $payload    = $this->buildGoogleEventPayload($event);

        // Update if already synced, otherwise create
        if ($event->google_event_id) {
            $response = Http::withToken($token->access_token)
                ->put(self::API_BASE . "/calendars/{$calendarId}/events/{$event->google_event_id}", $payload);
        } else {
            $response = Http::withToken($token->access_token)
                ->post(self::API_BASE . "/calendars/{$calendarId}/events", $payload);
        }

        if (! $response->successful()) {
            Log::warning('GoogleCalendar: pushEvent failed', [
                'event_id' => $event->id,
                'status'   => $response->status(),
                'body'     => $response->json(),
            ]);
            return null;
        }

        $googleEventId = $response->json('id');

        $event->update([
            'google_event_id'  => $googleEventId,
            'google_synced_at' => now(),
        ]);

        return $googleEventId;
    }

    /**
     * Delete a CalendarEvent from Google Calendar.
     */
    public function deleteEvent(CalendarEvent $event, int $userId, ?string $businessId): bool
    {
        $token = $this->getToken($userId, $businessId);
        if (! $token || ! $event->google_event_id) {
            return false;
        }

        if ($token->isExpired() && ! $this->refreshAccessToken($token)) {
            return false;
        }

        $response = Http::withToken($token->access_token)
            ->delete(self::API_BASE . "/calendars/{$token->calendar_id}/events/{$event->google_event_id}");

        return $response->successful() || $response->status() === 410; // 410 = already deleted
    }

    // ── Payload builder ───────────────────────────────────────────────────

    private function buildGoogleEventPayload(CalendarEvent $event): array
    {
        $payload = [
            'summary'     => $event->title,
            'description' => $event->description,
        ];

        if ($event->all_day) {
            $payload['start'] = ['date' => $event->starts_at->toDateString()];
            $payload['end']   = ['date' => ($event->ends_at ?? $event->starts_at)->toDateString()];
        } else {
            $payload['start'] = ['dateTime' => $event->starts_at->toIso8601String(), 'timeZone' => config('app.timezone', 'UTC')];
            $payload['end']   = ['dateTime' => ($event->ends_at ?? $event->starts_at->copy()->addHour())->toIso8601String(), 'timeZone' => config('app.timezone', 'UTC')];
        }

        return $payload;
    }
}
