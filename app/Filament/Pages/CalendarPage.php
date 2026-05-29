<?php

namespace App\Filament\Pages;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarToken;
use App\Services\Calendar\CalendarFeedService;
use App\Services\Calendar\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CalendarPage extends BasePage
{
    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-calendar-days';
    protected static \UnitEnum|string|null  $navigationGroup = 'Productivity';
    protected static ?string                $navigationLabel = 'Calendar';
    protected static ?int                   $navigationSort  = 0;
    protected string $view = 'filament.pages.calendar';

    // ── View data ─────────────────────────────────────────────────────────

    public function getGoogleConnected(): bool
    {
        return GoogleCalendarToken::where('user_id', auth()->id())
            ->where('business_id', currentBusiness()?->id)
            ->exists();
    }

    public function getGoogleConnectUrl(): string
    {
        return route('admin.google-calendar.connect');
    }

    public function getGoogleDisconnectUrl(): string
    {
        return route('admin.google-calendar.disconnect');
    }

    // GC-4: warn when connected but refresh_token is missing
    public function mount(): void
    {
        $userId     = auth()->id();
        $businessId = currentBusiness()?->id;
        $service    = app(GoogleCalendarService::class);

        if ($this->getGoogleConnected() && ! $service->hasValidRefreshToken($userId, $businessId)) {
            Notification::make()
                ->title('Google Calendar: reconnect required')
                ->body('Your Google token cannot be refreshed automatically. Reconnect to restore sync.')
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('reconnect')
                        ->label('Reconnect')
                        ->url(route('admin.google-calendar.connect'))
                        ->button(),
                ])
                ->send();
        }
    }

    /**
     * Quick-create a CalendarEvent from the inline modal.
     */
    public function quickCreate(string $title, string $type, string $startsAt, bool $allDay = false): void
    {
        $v = Validator::make(
            ['title' => $title, 'type' => $type, 'starts_at' => $startsAt],
            [
                'title'     => ['required', 'string', 'max:255'],
                'type'      => ['required', 'in:meeting,call,deadline,reminder,task'],
                'starts_at' => ['required', 'date'],
            ]
        );

        if ($v->fails()) {
            Notification::make()->title('Validation error')->body($v->errors()->first())->danger()->send();
            return;
        }

        $event = CalendarEvent::create([
            'business_id' => currentBusiness()?->id,
            'user_id'     => auth()->id(),
            'title'       => strip_tags($title),
            'type'        => $type,
            'starts_at'   => Carbon::parse($startsAt),
            'all_day'     => $allDay,
            'status'      => 'scheduled',
        ]);

        if ($this->getGoogleConnected()) {
            app(GoogleCalendarService::class)->pushEvent($event, auth()->id(), currentBusiness()?->id);
        }

        Notification::make()->title('Event created')->success()->send();
        $this->dispatch('calendarRefresh');
    }

    /**
     * Delete a CalendarEvent (only records owned by this business).
     */
    public function deleteCalendarEvent(int $id): void
    {
        $event = CalendarEvent::where('business_id', currentBusiness()?->id)->findOrFail($id);
        $event->delete();

        Notification::make()->title('Event deleted')->success()->send();
        $this->dispatch('calendarRefresh');
    }

    /**
     * Reschedule via drag & drop / resize.
     */
    public function moveCalendarEvent(int $id, string $startsAt, ?string $endsAt = null): void
    {
        $event = CalendarEvent::where('business_id', currentBusiness()?->id)->findOrFail($id);
        $event->update([
            'starts_at' => Carbon::parse($startsAt),
            'ends_at'   => $endsAt ? Carbon::parse($endsAt) : null,
        ]);

        $this->dispatch('calendarRefresh');
    }

    /**
     * Push all unsynced events to Google Calendar.
     */
    public function syncAllToGoogle(): void
    {
        if (! $this->getGoogleConnected()) {
            Notification::make()->title('Google Calendar not connected')->danger()->send();
            return;
        }

        $service = app(GoogleCalendarService::class);
        $events  = CalendarEvent::where('business_id', currentBusiness()?->id)
            ->whereNull('google_event_id')
            ->get();

        $synced = 0;
        $failed = 0;
        foreach ($events as $event) {
            if ($service->pushEvent($event, auth()->id(), currentBusiness()?->id)) {
                $synced++;
            } else {
                $failed++;
            }
        }

        if ($events->isEmpty()) {
            Notification::make()->title('All events already synced to Google Calendar')->success()->send();
        } elseif ($failed === 0) {
            Notification::make()->title("{$synced} event(s) synced to Google Calendar")->success()->send();
        } elseif ($synced === 0) {
            Notification::make()->title('Google Calendar sync failed')->body('Check that Google Calendar API is enabled in your Google Cloud Console.')->danger()->send();
        } else {
            Notification::make()->title("{$synced} synced, {$failed} failed")->body('Some events could not be synced. Check Google Calendar API settings.')->warning()->send();
        }

        $this->dispatch('calendarRefresh');
    }

    /**
     * Push a single event to Google Calendar on demand.
     */
    public function syncEventToGoogle(int $id): void
    {
        $event  = CalendarEvent::where('business_id', currentBusiness()?->id)->findOrFail($id);
        $result = app(GoogleCalendarService::class)->pushEvent($event, auth()->id(), currentBusiness()?->id);

        if ($result) {
            Notification::make()->title('Synced to Google Calendar')->success()->send();
        } else {
            Notification::make()->title('Google sync failed')->danger()->send();
        }

        $this->dispatch('calendarRefresh');
    }

    /**
     * Pull events from Google Calendar and create local CalendarEvent records.
     * GC-2: accepts optional date range (shown as form in the header action).
     */
    public function importFromGoogle(?Carbon $from = null, ?Carbon $to = null): void
    {
        if (! $this->getGoogleConnected()) {
            Notification::make()->title('Google Calendar not connected')->danger()->send();
            return;
        }

        $service    = app(GoogleCalendarService::class);
        $start      = ($from ?? Carbon::now()->subDays(30))->startOfDay();
        $end        = ($to   ?? Carbon::now()->addDays(90))->endOfDay();
        $businessId = currentBusiness()?->id;

        // Fetch from all calendars (primary + holidays, birthdays, etc.)
        $calendars = $service->fetchCalendarList(auth()->id(), $businessId);
        if (empty($calendars)) {
            $calendars = [['id' => 'primary', 'summary' => 'Primary']];
        }

        $imported        = 0;
        $skipped         = 0;
        $failed          = 0;
        $failedSummaries = [];

        foreach ($calendars as $calendar) {
            $googleEvents = $service->fetchEventsFromGoogle(
                auth()->id(), $businessId, $start, $end, $calendar['id']
            );

            // GC-5: null = API error for this calendar
            if ($googleEvents === null) {
                $failed++;
                $failedSummaries[] = $calendar['summary'];
                Log::warning('CalendarPage: importFromGoogle skipped calendar (API error)', [
                    'id'      => $calendar['id'],
                    'summary' => $calendar['summary'],
                ]);
                continue;
            }

            Log::info('CalendarPage: importFromGoogle calendar', [
                'id'          => $calendar['id'],
                'summary'     => $calendar['summary'],
                'event_count' => count($googleEvents),
            ]);

            foreach ($googleEvents as $gEvent) {
                $googleId = $gEvent['id'] ?? null;
                if (! $googleId) {
                    continue;
                }

                if (CalendarEvent::where('google_event_id', $googleId)->exists()) {
                    $skipped++;
                    continue;
                }

                if (($gEvent['status'] ?? '') === 'cancelled') {
                    continue;
                }

                $allDay   = isset($gEvent['start']['date']) && ! isset($gEvent['start']['dateTime']);
                $startsAt = $allDay
                    ? Carbon::parse($gEvent['start']['date'])->startOfDay()
                    : Carbon::parse($gEvent['start']['dateTime']);

                $endsAt = null;
                if (isset($gEvent['end']['date'])) {
                    $candidate = Carbon::parse($gEvent['end']['date'])->subDay()->startOfDay();
                    $endsAt    = $candidate->gt($startsAt) ? $candidate : null;
                } elseif (isset($gEvent['end']['dateTime'])) {
                    $endsAt = Carbon::parse($gEvent['end']['dateTime']);
                }

                CalendarEvent::create([
                    'business_id'      => $businessId,
                    'user_id'          => auth()->id(),
                    'title'            => strip_tags($gEvent['summary'] ?? '(no title)'),
                    'description'      => isset($gEvent['description']) ? strip_tags($gEvent['description']) : null,
                    'type'             => $this->resolveEventType($calendar['id']), // GC-1
                    'starts_at'        => $startsAt,
                    'ends_at'          => $endsAt,
                    'all_day'          => $allDay,
                    'status'           => 'scheduled',
                    'google_event_id'  => $googleId,
                    'google_synced_at' => now(),
                ]);

                $imported++;
            }
        }

        // Success notification
        if ($imported > 0) {
            $msg = "{$imported} event(s) imported from Google Calendar";
            if ($skipped > 0) {
                $msg .= " ({$skipped} already existed)";
            }
        } elseif ($skipped > 0) {
            $msg = "All {$skipped} Google events already exist in the system";
        } else {
            $msg = 'No events found in Google Calendar for the selected range';
        }
        Notification::make()->title($msg)->success()->send();

        // GC-5: warn about calendars that failed
        if ($failed > 0) {
            Notification::make()
                ->title("{$failed} calendar(s) could not be fetched")
                ->body('Failed: ' . implode(', ', $failedSummaries))
                ->warning()
                ->send();
        }

        $this->dispatch('calendarRefresh');
    }

    /**
     * GC-1: Resolve CalendarEvent type from Google calendar ID.
     * Holiday / birthday calendars → 'reminder'; everything else → 'meeting'.
     */
    private function resolveEventType(string $calendarId): string
    {
        if (
            str_contains($calendarId, '#holiday') ||
            str_contains($calendarId, '#holidays') ||
            str_contains($calendarId, '#birthdays')
        ) {
            return 'reminder';
        }

        return 'meeting';
    }

    // ── Header actions ────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newEvent')
                ->label('New Event')
                ->icon('heroicon-o-plus')
                ->url(route('filament.admin.resources.calendar-events.create')),

            Action::make('syncAll')
                ->label('Sync All to Google')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('syncAllToGoogle')
                ->visible(fn () => $this->getGoogleConnected()),

            Action::make('importFromGoogle')
                ->label('Import from Google')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('info')
                ->form([
                    DatePicker::make('from')
                        ->label('Import from')
                        ->default(now()->subDays(30)->toDateString())
                        ->required(),
                    DatePicker::make('to')
                        ->label('Import to')
                        ->default(now()->addDays(90)->toDateString())
                        ->required(),
                ])
                ->action(fn (array $data) => $this->importFromGoogle(
                    Carbon::parse($data['from'])->startOfDay(),
                    Carbon::parse($data['to'])->endOfDay(),
                ))
                ->visible(fn () => $this->getGoogleConnected()),

            Action::make('connectGoogle')
                ->label('Connect Google Calendar')
                ->icon('heroicon-o-link')
                ->color('info')
                ->url($this->getGoogleConnectUrl())
                ->visible(fn () => ! $this->getGoogleConnected()),

            Action::make('disconnectGoogle')
                ->label('Disconnect Google')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->url($this->getGoogleDisconnectUrl())
                ->visible(fn () => $this->getGoogleConnected()),
        ];
    }
}
