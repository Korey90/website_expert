<?php

return [
    '404' => [
        'title' => 'Page not found',
        'message' => "The page you're looking for doesn't exist or may have been moved.",
    ],
    '403' => [
        'title' => 'Access denied',
        'message' => "You don't have permission to view this page.",
    ],
    '419' => [
        'title' => 'Session expired',
        'message' => 'Your session expired for security reasons. Please refresh the page and try again.',
    ],
    '429' => [
        'title' => 'Too many requests',
        'message' => "You've sent too many requests in a short time. Please wait a moment and try again.",
    ],
    '500' => [
        'title' => 'Something went wrong',
        'message' => "An unexpected error occurred on our end. We've been notified and are already looking into it.",
    ],
    '503' => [
        'title' => 'Down for maintenance',
        'message' => "We're performing scheduled maintenance. We'll be back online shortly.",
    ],
    'home_link' => 'Back to homepage',
    'support_text' => 'If the problem persists, contact us at',
];
