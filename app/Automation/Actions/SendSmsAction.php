<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Models\SmsTemplate;
use App\Services\ClientNotificationGate;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SendSmsAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $client = $this->resolveClient($context);
        if ($client && ! ClientNotificationGate::canSendSms($client)) {
            throw new ActionSkippedException("Client #{$client->id} has notify_sms disabled.");
        }

        $phone = $this->resolveRecipientPhone($action['recipient'] ?? $action['to'] ?? 'client', $context);
        if (! $phone) {
            throw new ActionSkippedException(
                'No phone number resolved. Lead/client may have no phone, or recipient value is invalid.'
            );
        }

        $template = SmsTemplate::find($action['template_id'] ?? null);
        if (! $template) {
            throw new ActionSkippedException(
                "SmsTemplate #{$action['template_id']} not found or template_id missing in action config."
            );
        }

        $message = $template->render($this->buildTemplateVars($context));

        app(SmsService::class)->send($phone, $message);
    }
}

