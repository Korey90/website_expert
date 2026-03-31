<?php

namespace App\Automation\Actions;

use Illuminate\Support\Facades\Mail;

class SendInternalEmailAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $to   = $action['to'] ?? config('mail.admin_address', 'admin@websiteexpert.co.uk');
        $body = $action['body'] ?? 'Automation triggered: ' . $triggerEvent;

        Mail::raw($body, function ($msg) use ($to) {
            $msg->to($to)->subject('WebsiteExpert Automation Alert');
        });
    }
}
