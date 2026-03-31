<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends BasePortalController
{
    public function settings(): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Portal/NotificationSettings', [
            'client' => $client->only('id', 'company_name'),
            'prefs'  => [
                'notify_email_transactional' => (bool) $client->notify_email_transactional,
                'notify_email_projects'      => (bool) $client->notify_email_projects,
                'notify_email_marketing'     => (bool) $client->notify_email_marketing,
                'notify_sms'                 => (bool) $client->notify_sms,
                'updated_at'                 => $client->communication_prefs_updated_at?->toISOString(),
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client) {
            abort(403);
        }

        $validated = $request->validate([
            'notify_email_transactional' => ['required', 'boolean'],
            'notify_email_projects'      => ['required', 'boolean'],
            'notify_email_marketing'     => ['required', 'boolean'],
            'notify_sms'                 => ['required', 'boolean'],
        ]);

        $client->update(array_merge($validated, [
            'communication_prefs_updated_at' => now(),
        ]));

        return redirect()->route('portal.settings.notifications')
            ->with('success', 'Communication preferences saved.');
    }
}
