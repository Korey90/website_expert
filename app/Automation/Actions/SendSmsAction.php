<?php

namespace App\Automation\Actions;

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
            return;
        }

        $phone = $this->resolveRecipientPhone($action['recipient'] ?? $action['to'] ?? 'client', $context);
        if (! $phone) {
            return;
        }

        $template = SmsTemplate::find($action['template_id'] ?? null);
        if (! $template) {
            Log::warning('SendSmsAction: no valid template_id.', ['action' => $action]);
            return;
        }

        $message = $template->render($this->buildTemplateVars($context));

        app(SmsService::class)->send($phone, $message);
    }
}
