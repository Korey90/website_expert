<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityWebhookRequest;
use App\Models\SecurityEvent;
use Illuminate\Http\JsonResponse;

class SecurityWebhookController extends Controller
{
    public function __invoke(SecurityWebhookRequest $request): JsonResponse
    {
        $jail = $request->input('jail');

        $event = SecurityEvent::create([
            'ip'          => $request->input('ip'),
            'jail'        => $jail,
            'attack_type' => SecurityEvent::attackTypeLabel($jail),
            'failures'    => $request->input('failures', 0),
            'country'     => $request->input('country'),
            'city'        => $request->input('city'),
            'isp'         => $request->input('isp'),
            'action'      => $request->input('action'),
            'banned_at'   => $request->input('action') === 'banned' ? now() : null,
            'unbanned_at' => $request->input('action') === 'unbanned' ? now() : null,
        ]);

        return response()->json(['ok' => true, 'id' => $event->id], 201);
    }
}
