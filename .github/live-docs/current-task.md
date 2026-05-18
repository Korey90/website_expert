# Current Task

**Status:** Done

**Task Title:** Google Calendar — Activity History, Sync All, Import Multi-Calendars, Calendar Stability
**Requested by:** User (2026-05-18)
**Completed:** 2026-05-18

**Implemented:**
- Activity History: nowe typy ENUM `event_scheduled` + `event_deleted` (2 migracje)
- `LeadActivity` icons/colors dla obu nowych typów
- `ViewLead.createLeadEvent()`: log `event_scheduled` po zapisaniu eventu
- `ViewLead.deleteLeadEvent()`: log `event_deleted` po usunięciu
- `CalendarPage.syncAllToGoogle()`: masowy push eventów bez `google_event_id`
- `CalendarPage.importFromGoogle()`: import ze WSZYSTKICH kalendarzy (primary + święta + urodziny)
- `CalendarPage.quickCreate()`: auto-sync do Google gdy konto podłączone
- `GoogleCalendarService.fetchCalendarList()`: pobieranie listy kalendarzy z `GET /users/me/calendarList`
- `GoogleCalendarService.fetchEventsFromGoogle()`: opcjonalny param `calendarId` + `rawurlencode` ID
- `wire:ignore` na divie karty kalendarza (Livewire Morphdom fix)
- Overlay loadingu jako absolute div zamiast `x-show` na `#calendar` (FullCalendar DOM fix)
- Alpine `googleConnected` jako property zamiast Blade `@if` (hydration fix)

**Tests:** 265/265 ✅ (było 260, +5 z nowych funkcji)

**Last Updated:** 2026-05-18