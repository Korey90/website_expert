<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Notifications
// -----------------------------------------------------------------------

// Notification follow-through: marks most-recent unread notification as read, then redirects.
// The URL redirect is intentionally limited to relative paths to prevent open-redirect attacks.
Route::get('/notification-follow', function (Request $request) {
    $to = $request->query('to', '/admin');
    if (! preg_match('#^/[^/]#', $to)) {
        $to = '/admin';
    }
    $id = $request->query('id');
    if ($id && preg_match('/^[0-9a-f\-]{36}$/i', $id)) {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    } else {
        auth()->user()?->unreadNotifications()->latest()->first()?->markAsRead();
    }
    return redirect($to);
})->middleware('auth')->name('notification.follow');

// JS keepalive fetch endpoint — marks a specific notification as read.
Route::post('/notification-mark-read', function (Request $request) {
    $id = $request->input('id', '');
    if ($id && preg_match('/^[0-9a-f\-]{36}$/i', $id)) {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    }
    return response()->noContent();
})->middleware('auth')->name('notification.mark-read');
