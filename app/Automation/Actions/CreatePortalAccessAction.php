<?php

namespace App\Automation\Actions;

use App\Mail\PortalInviteMail;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreatePortalAccessAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $client = null;

        if (isset($context['client_id'])) {
            $client = Client::find($context['client_id']);
        } elseif (isset($context['lead_id'])) {
            $lead   = Lead::with('client')->find($context['lead_id']);
            $client = $lead?->client;
        }

        if (! $client) {
            Log::warning('CreatePortalAccessAction: no client found.', ['context' => $context]);
            return;
        }

        if ($client->portal_user_id) {
            return;
        }

        $email = $client->primary_contact_email;
        if (! $email) {
            Log::warning("CreatePortalAccessAction: client #{$client->id} has no email.");
            return;
        }

        $user  = User::where('email', $email)->first();
        $plain = null;

        if (! $user) {
            $plain = Str::password(12, symbols: false);
            $user  = User::create([
                'name'     => $client->primary_contact_name ?: $client->company_name,
                'email'    => $email,
                'password' => bcrypt($plain),
            ]);
            $user->assignRole('client');
        }

        $client->update(['portal_user_id' => $user->id]);

        if ($plain) {
            Mail::to($email)->queue(new PortalInviteMail(
                clientName:    $client->primary_contact_name ?: $client->company_name,
                loginEmail:    $email,
                plainPassword: $plain,
                loginUrl:      config('app.url') . '/client',
                companyName:   config('app.name', 'WebsiteExpert'),
            ));
        }

        Log::info("CreatePortalAccessAction: portal access created for client #{$client->id} (user #{$user->id}).");
    }
}
