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

        // New path: EmailTemplate by ID (accepts 'email_template_id' or 'template_id')
        $templateId = $action['email_template_id'] ?? $action['template_id'] ?? null;
        if (! empty($templateId)) {
            $template = EmailTemplate::where('is_active', true)->find($templateId);
            if (! $template) {
                throw new ActionSkippedException(
                    "EmailTemplate #{$templateId} not found or is inactive."
                );
            }
            $vars    = $this->buildTemplateVars($context);
            $locale  = $context['locale'] ?? app()->getLocale();
            $locData = $template->getForLocale($locale);
            $subject = $this->interpolate($locData['subject'] ?? '', $vars);
            $body    = $this->interpolate($locData['body_html'] ?: ($locData['body_text'] ?? ''), $vars);

            // Already running inside a queued job — send synchronously, no double-hop
            Mail::to($to)->send(new ClientEmailMail($subject, $body));
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
