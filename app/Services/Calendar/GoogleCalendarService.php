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
     * Check whether the stored token has a refresh_token.
     * Without it, once the access_token expires the connection is permanently broken.
     */
    public function hasValidRefreshToken(int $userId, ?string $businessId): bool
    {
        return GoogleCalendarToken::where('user_id', $userId)
            ->where('business_id', $businessId)
            ->whereNotNull('refresh_token')
            ->exists();
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

        // Proactively refresh if expired; also retry once on 401
        if ($token->isExpired()) {
            if (! $this->refreshAccessToken($token)) {
                return null;
            }
            $token->refresh();
        }

        $calendarId = $token->calendar_id;
        $payload    = $this->buildGoogleEventPayload($event);

        $response = $this->doApiCall($token, $event, $calendarId, $payload);

        // 401 = access token expired despite isExpired() returning false → refresh & retry once
        if ($response->status() === 401) {
            if (! $this->refreshAccessToken($token)) {
                return null;
            }
            $token->refresh();
            $response = $this->doApiCall($token, $event, $calendarId, $payload);
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

    /**
     * List all calendars in the user's Google Calendar account.
     * Returns array of ['id' => string, 'summary' => string].
     */
    public function fetchCalendarList(int $userId, ?string $businessId): array
    {
        $token = $this->getToken($userId, $businessId);
        if (! $token) {
            return [];
        }

        if ($token->isExpired()) {
            if (! $this->refreshAccessToken($token)) {
                return [];
            }
            $token->refresh();
        }

        $allItems  = [];
        $pageToken = null;
        $retried   = false;

        do {
            $params = ['maxResults' => 250];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::withToken($token->access_token)
                ->get(self::API_BASE . '/users/me/calendarList', $params);

            if ($response->status() === 401 && ! $retried) {
                $retried = true;
                if (! $this->refreshAccessToken($token)) {
                    break;
                }
                $token->refresh();
                $response = Http::withToken($token->access_token)
                    ->get(self::API_BASE . '/users/me/calendarList', $params);
            }

            if (! $response->successful()) {
                Log::warning('GoogleCalendar: fetchCalendarList failed', [
                    'user_id' => $userId,
                    'status'  => $response->status(),
                    'body'    => $response->json(),
                ]);
                break;
            }

            $data      = $response->json();
            $allItems  = array_merge($allItems, $data['items'] ?? []);
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        if (empty($allItems)) {
            return [['id' => $token->calendar_id, 'summary' => 'Primary']];
        }

        $calendars = array_values(array_map(
            fn ($cal) => [
                'id'      => $cal['id'],
                'summary' => $cal['summary'] ?? $cal['id'],
            ],
            array_filter(
                $allItems,
                fn ($cal) => ($cal['deleted'] ?? false) === false,
            )
        ));

        Log::info('GoogleCalendar: fetchCalendarList', [
            'user_id'   => $userId,
            'count'     => count($calendars),
            'calendars' => array_column($calendars, 'id'),
        ]);

        return $calendars;
    }

    /**
     * Fetch events from Google Calendar for the given date range.
     * Returns raw Google API items array.
     */
    public function fetchEventsFromGoogle(
        int     $userId,
        ?string $businessId,
        Carbon  $start,
        Carbon  $end,
        ?string $calendarId = null,
    ): ?array {
        $token = $this->getToken($userId, $businessId);
        if (! $token) {
            return null;
        }

        if ($token->isExpired()) {
            if (! $this->refreshAccessToken($token)) {
                return null;
            }
            $token->refresh();
        }

        $items     = [];
        $pageToken = null;
        $calId     = $calendarId ?? $token->calendar_id;
        $calIdEnc  = rawurlencode($calId);

        do {
            $params = [
                'timeMin'      => $start->toIso8601String(),
                'timeMax'      => $end->toIso8601String(),
                'singleEvents' => 'true',
                'orderBy'      => 'startTime',
                'maxResults'   => 250,
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::withToken($token->access_token)
                ->get(self::API_BASE . "/calendars/{$calIdEnc}/events", $params);

            if ($response->status() === 401) {
                if (! $this->refreshAccessToken($token)) {
                    return null;
                }
                $token->refresh();
                $response = Http::withToken($token->access_token)
                    ->get(self::API_BASE . "/calendars/{$calIdEnc}/events", $params);
            }

            if (! $response->successful()) {
                Log::warning('GoogleCalendar: fetchEvents failed', [
                    'user_id'     => $userId,
                    'calendar_id' => $calId,
                    'status'      => $response->status(),
                    'body'        => $response->json(),
                ]);
                return null;
            }

            $data      = $response->json();
            $items     = array_merge($items, $data['items'] ?? []);
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $items;
    }

    private function doApiCall(GoogleCalendarToken $token, CalendarEvent $event, string $calendarId, array $payload): \Illuminate\Http\Client\Response
    {
        if ($event->google_event_id) {
            return Http::withToken($token->access_token)
                ->put(self::API_BASE . "/calendars/{$calendarId}/events/{$event->google_event_id}", $payload);
        }

        return Http::withToken($token->access_token)
            ->post(self::API_BASE . "/calendars/{$calendarId}/events", $payload);
    }

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
