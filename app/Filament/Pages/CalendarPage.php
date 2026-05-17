<?php

namespace App\Filament\Pages;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarToken;
use App\Services\Calendar\CalendarFeedService;
use App\Services\Calendar\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
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

    // ── Livewire actions (called from Alpine via $wire) ───────────────────

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

        CalendarEvent::create([
            'business_id' => currentBusiness()?->id,
            'user_id'     => auth()->id(),
            'title'       => strip_tags($title),
            'type'        => $type,
            'starts_at'   => Carbon::parse($startsAt),
            'all_day'     => $allDay,
            'status'      => 'scheduled',
        ]);

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

    // ── Header actions ────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newEvent')
                ->label('New Event')
                ->icon('heroicon-o-plus')
                ->url(route('filament.admin.resources.calendar-events.create')),

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
