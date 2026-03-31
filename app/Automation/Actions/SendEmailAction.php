<?php

namespace App\Automation\Actions;

use App\Services\ClientNotificationGate;
use Illuminate\Support\Facades\Mail;

class SendEmailAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $client = $this->resolveClient($context);
        if ($client && ! ClientNotificationGate::canSendEmail($client, 'marketing')) {
            return;
        }

        $to = $this->resolveRecipientEmail($action['recipient'] ?? 'client', $context);
        if (! $to) {
            return;
        }

        $mailable = $this->resolveMailable($action['template'] ?? null, $context);
        if ($mailable) {
            Mail::to($to)->queue($mailable);
        }
    }
}
