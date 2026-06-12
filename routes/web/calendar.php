<?php

use App\Http\Controllers\GoogleCalendarController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Google Calendar OAuth & JSON feed — auth + has.business
// -----------------------------------------------------------------------

Route::middleware(['auth', 'verified', 'has.business'])
    ->prefix('admin/google-calendar')
    ->name('admin.google-calendar.')
    ->group(function () {
        Route::get('/connect',    [GoogleCalendarController::class, 'connect'])->name('connect');
        Route::get('/callback',   [GoogleCalendarController::class, 'callback'])->name('callback');
        Route::get('/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('disconnect');
    });

// Calendar Events JSON feed (used by FullCalendar AJAX)
Route::middleware(['auth', 'verified', 'has.business'])
    ->get('/admin/calendar/events', function (\Illuminate\Http\Request $request) {
        $businessId = currentBusiness()?->id;
        $from = \Illuminate\Support\Carbon::parse($request->query('start', now()->startOfMonth()));
        $to   = \Illuminate\Support\Carbon::parse($request->query('end',   now()->endOfMonth()));

        $events = app(\App\Services\Calendar\CalendarFeedService::class)->getEvents($businessId, $from, $to);

        return response()->json($events);
    })->name('admin.calendar.events');
