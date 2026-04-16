<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Mail\ClientEmailMail;
use App\Models\EmailTemplate;
use App\Services\ClientNotificationGate;
use Illuminate\Support\Facades\Mail;

class SendEmailAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $client = $this->resolveClient($context);
        if ($client && ! ClientNotificationGate::canSendEmail($client, 'marketing')) {
            throw new ActionSkippedException("Client #{$client->id} has notify_email_marketing disabled.");
        }

        $to = $this->resolveRecipientEmail($action['recipient'] ?? $action['to'] ?? 'client', $context);
        if (! $to) {
            throw new ActionSkippedException(
                'No email address resolved. Lead/client may have no email, or recipient value is invalid.'
            );
        }

        // New path: EmailTemplate by ID
        if (! empty($action['email_template_id'])) {
            $template = EmailTemplate::where('is_active', true)->find($action['email_template_id']);
            if (! $template) {
                throw new ActionSkippedException(
                    "EmailTemplate #{$action['email_template_id']} not found or is inactive."
                );
            }
            $vars    = $this->buildTemplateVars($context);
            $locale  = app()->getLocale();
            $locData = $template->getForLocale($locale);
            $subject = $this->interpolate($locData['subject'], $vars);
            $body    = $this->interpolate($locData['body_html'] ?: $locData['body_text'], $vars);

            Mail::to($to)->queue(new ClientEmailMail($subject, $body));
            return;
        }

        // Legacy path: mailable resolved by slug string
        $mailable = $this->resolveMailable($action['template'] ?? null, $context);
        if (! $mailable) {
            throw new ActionSkippedException(
                "Legacy template slug '{$action['template']}' could not be resolved to a Mailable."
            );
        }
        Mail::to($to)->queue($mailable);
    }
}
