<?php

namespace App\Jobs;

use App\Mail\InvoiceSentMail;
use App\Mail\NewLeadMail;
use App\Mail\ProjectStatusMail;
use App\Mail\QuoteSentMail;
use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Evaluates active AutomationRule records for a given trigger event and
 * executes matching actions (send_email, send_internal_email, add_tag).
 *
 * Dispatched by AutomationEventListener after relevant model changes.
 */
class ProcessAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $triggerEvent,
        public readonly array  $context,  // e.g. ['lead_id' => 5, 'project_id' => 2]
    ) {}

    public function handle(): void
    {
        $rules = AutomationRule::where('trigger_event', $this->triggerEvent)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $this->conditionsMet($rule->conditions ?? [])) {
                continue;
            }

            foreach ($rule->actions ?? [] as $action) {
                $this->executeAction($action);
            }
        }
    }

    private function conditionsMet(array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field    = $condition['field']    ?? null;
            $operator = $condition['operator'] ?? '=';
            $value    = $condition['value']    ?? null;

            $contextValue = $this->context[$field] ?? null;

            $passes = match ($operator) {
                '='  => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>'  => $contextValue > $value,
                '<'  => $contextValue < $value,
                'contains' => str_contains((string) $contextValue, (string) $value),
                default => true,
            };

            if (! $passes) {
                return false;
            }
        }

        return true;
    }

    private function executeAction(array $action): void
    {
        $type = $action['type'] ?? null;

        try {
            match ($type) {
                'send_email'          => $this->sendEmail($action),
                'send_internal_email' => $this->sendInternalEmail($action),
                'add_tag'             => $this->addTag($action),
                'change_status'       => $this->changeStatus($action),
                default               => null,
            };
        } catch (\Throwable $e) {
            Log::error("AutomationRule action failed [{$type}]: " . $e->getMessage(), [
                'context' => $this->context,
                'action'  => $action,
            ]);
        }
    }

    private function sendEmail(array $action): void
    {
        $to = $this->resolveRecipientEmail($action['recipient'] ?? 'client');
        if (! $to) {
            return;
        }

        $mailable = $this->resolveMailable($action['template'] ?? null);
        if ($mailable) {
            Mail::to($to)->queue($mailable);
        }
    }

    private function sendInternalEmail(array $action): void
    {
        $to   = $action['to'] ?? config('mail.admin_address', 'admin@websiteexpert.co.uk');
        $body = $action['body'] ?? 'Automation triggered: ' . $this->triggerEvent;

        Mail::raw($body, function ($msg) use ($to) {
            $msg->to($to)->subject('WebsiteExpert Automation Alert');
        });
    }

    private function addTag(array $action): void
    {
        $tag = $action['tag'] ?? null;
        if (! $tag) {
            return;
        }

        if (isset($this->context['lead_id'])) {
            $lead = Lead::find($this->context['lead_id']);
            if ($lead) {
                $tags   = $lead->tags ?? [];
                $tags[] = $tag;
                $lead->update(['tags' => array_unique($tags)]);
            }
        }
    }

    private function changeStatus(array $action): void
    {
        $newStatus = $action['status'] ?? null;
        if (! $newStatus) {
            return;
        }

        foreach (['lead_id' => Lead::class, 'project_id' => Project::class, 'invoice_id' => Invoice::class] as $key => $model) {
            if (isset($this->context[$key])) {
                $model::find($this->context[$key])?->update(['status' => $newStatus]);
            }
        }
    }

    private function resolveRecipientEmail(string $recipient): ?string
    {
        return match ($recipient) {
            'client' => $this->resolveClientEmail(),
            'admin'  => config('mail.admin_address', 'admin@websiteexpert.co.uk'),
            default  => filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : null,
        };
    }

    private function resolveClientEmail(): ?string
    {
        if (isset($this->context['client_id'])) {
            return Client::find($this->context['client_id'])?->primary_contact_email;
        }
        if (isset($this->context['lead_id'])) {
            $lead = Lead::find($this->context['lead_id']);
            return $lead?->client?->primary_contact_email ?? $lead?->email;
        }
        if (isset($this->context['project_id'])) {
            return Project::find($this->context['project_id'])?->client?->primary_contact_email;
        }
        if (isset($this->context['invoice_id'])) {
            return Invoice::find($this->context['invoice_id'])?->client?->primary_contact_email;
        }
        if (isset($this->context['quote_id'])) {
            return Quote::find($this->context['quote_id'])?->client?->primary_contact_email;
        }

        return null;
    }

    private function resolveMailable(string|null $template): ?object
    {
        return match ($template) {
            'invoice_sent'    => isset($this->context['invoice_id'])
                ? new InvoiceSentMail(Invoice::findOrFail($this->context['invoice_id']))
                : null,
            'quote_sent'      => isset($this->context['quote_id'])
                ? new QuoteSentMail(Quote::findOrFail($this->context['quote_id']))
                : null,
            'project_status'  => isset($this->context['project_id'])
                ? new ProjectStatusMail(
                    Project::findOrFail($this->context['project_id']),
                    $this->context['old_status'] ?? 'unknown',
                )
                : null,
            'new_lead'        => isset($this->context['lead_id'])
                ? new NewLeadMail($this->context, $this->context['lead_id'])
                : null,
            default => null,
        };
    }
}
