<?php

namespace App\Jobs;

use App\Mail\InvoiceSentMail;
use App\Mail\NewLeadMail;
use App\Mail\PortalInviteMail;
use App\Mail\ProjectStatusMail;
use App\Mail\QuoteSentMail;
use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quote;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\SmsService;
use App\Services\ClientNotificationGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
        public readonly array  $context,
        public readonly ?int   $singleRuleId = null, // used for delayed per-rule dispatch
    ) {}

    public function handle(): void
    {
        // When dispatched with a specific rule ID (delayed execution), run only that rule.
        if ($this->singleRuleId !== null) {
            $rule = AutomationRule::where('is_active', true)->find($this->singleRuleId);
            if ($rule && $this->conditionsMet($rule->conditions ?? [])) {
                foreach ($rule->actions ?? [] as $action) {
                    $this->executeAction($action);
                }
            }
            return;
        }

        $rules = AutomationRule::where('trigger_event', $this->triggerEvent)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $this->conditionsMet($rule->conditions ?? [])) {
                continue;
            }

            $delay = (int) ($rule->delay_minutes ?? 0);

            if ($delay > 0) {
                // Dispatch a new job scoped to this rule with the specified delay.
                self::dispatch($this->triggerEvent, $this->context, $rule->id)
                    ->delay(now()->addMinutes($delay));
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
                'send_sms'            => $this->sendSms($action),
                'notify_admin'        => $this->notifyAdmin($action),
                'add_tag'              => $this->addTag($action),
                'change_status'        => $this->changeStatus($action),
                'create_portal_access' => $this->createPortalAccess($action),
                default                => null,
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
        $client = $this->resolveClient();
        if ($client && ! ClientNotificationGate::canSendEmail($client, 'marketing')) {
            return;
        }

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

    private function sendSms(array $action): void
    {
        $client = $this->resolveClient();
        if ($client && ! ClientNotificationGate::canSendSms($client)) {
            return;
        }

        $phone = $this->resolveRecipientPhone($action['recipient'] ?? $action['to'] ?? 'client');
        if (! $phone) {
            return;
        }

        $template = SmsTemplate::find($action['template_id'] ?? null);
        if (! $template) {
            Log::warning('ProcessAutomationJob: send_sms action has no valid template_id.', ['action' => $action]);
            return;
        }

        $message = $template->render($this->buildTemplateVars());

        app(SmsService::class)->send($phone, $message);
    }

    private function notifyAdmin(array $action): void
    {
        $vars  = array_merge($this->context, $this->buildTemplateVars());
        $title = $this->interpolate($action['title'] ?? 'Admin Notification', $vars);
        $body  = $this->interpolate($action['body']  ?? 'Event: ' . $this->triggerEvent, $vars);
        $url   = $this->interpolate($action['url']   ?? '', $vars);
        $icon  = $action['icon']  ?? 'heroicon-o-bell';
        $color = $action['color'] ?? 'primary';
        $roles = $action['roles'] ?? ['admin'];

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', (array) $roles))->get();

        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            // Pre-generate UUID so the follow URL can be built atomically in one DB insert.
            $notifId   = (string) Str::orderedUuid();
            $followUrl = $url ? route('notification.follow', ['to' => $url, 'id' => $notifId]) : null;

            $notification = FilamentNotification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->iconColor($color);

            if ($followUrl) {
                $notification->actions([
                    FilamentAction::make('view')
                        ->label('View')
                        ->url($followUrl),
                ]);
            }

            $data = $notification->toArray();
            $data['format'] = 'filament'; // required for Filament's query: where('data->format', 'filament')
            $data['duration'] = 'persistent'; // prevent Alpine auto-close (default is 6000ms which triggers notificationClosed → delete)
            unset($data['id']);

            $user->notifications()->create([
                'id'      => $notifId,
                'type'    => \Filament\Notifications\DatabaseNotification::class,
                'data'    => $data,
                'read_at' => null,
            ]);

            \Filament\Notifications\Events\DatabaseNotificationsSent::dispatch($user);
        }
    }

    private function interpolate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) ($value ?? ''), $template);
        }

        return $template;
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

    private function createPortalAccess(array $action): void
    {
        $client = null;

        if (isset($this->context['client_id'])) {
            $client = Client::find($this->context['client_id']);
        } elseif (isset($this->context['lead_id'])) {
            $lead   = Lead::with('client')->find($this->context['lead_id']);
            $client = $lead?->client;
        }

        if (! $client) {
            Log::warning('ProcessAutomationJob: create_portal_access — no client found.', ['context' => $this->context]);
            return;
        }

        // Already has a portal account — skip
        if ($client->portal_user_id) {
            return;
        }

        $email = $client->primary_contact_email;
        if (! $email) {
            Log::warning("ProcessAutomationJob: create_portal_access — client #{$client->id} has no email.");
            return;
        }

        // Reuse existing user if one already exists with this email
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

        Log::info("ProcessAutomationJob: portal access created for client #{$client->id} (user #{$user->id}).");
    }

    /**
     * Build a flat array of template variable values from the current context.
     * Used by SmsTemplate::render().
     */
    private function buildTemplateVars(): array
    {
        $vars = ['today' => now()->format('d M Y')];

        if (isset($this->context['lead_id'])) {
            $lead = Lead::with(['client', 'stage', 'assignedTo'])->find($this->context['lead_id']);
            if ($lead) {
                $vars['lead_title']    = $lead->title ?? '';
                $vars['client_name']   = $lead->client?->primary_contact_name ?? '';
                $vars['company_name']  = $lead->client?->company_name ?? '';
                $vars['stage_name']    = $lead->stage?->name ?? '';
                $vars['assigned_name'] = $lead->assignedTo?->name ?? '';
                $vars['project_name']  = $lead->project?->name ?? '';
            }
        }

        if (isset($this->context['project_id'])) {
            $project = Project::with(['client', 'assignedTo'])->find($this->context['project_id']);
            if ($project) {
                $vars['project_name']  = $project->name ?? '';
                $vars['client_name']   = $vars['client_name']  ?? ($project->client?->primary_contact_name ?? '');
                $vars['company_name']  = $vars['company_name'] ?? ($project->client?->company_name ?? '');
                $vars['assigned_name'] = $vars['assigned_name'] ?? ($project->assignedTo?->name ?? '');
            }
        }

        if (isset($this->context['invoice_id'])) {
            $invoice = Invoice::with('client')->find($this->context['invoice_id']);
            if ($invoice) {
                $vars['invoice_number'] = $invoice->invoice_number ?? "#{$invoice->id}";
                $vars['client_name']    = $vars['client_name']  ?? ($invoice->client?->primary_contact_name ?? '');
                $vars['company_name']   = $vars['company_name'] ?? ($invoice->client?->company_name ?? '');
            }
        }

        return $vars;
    }

    private function resolveRecipientEmail(string $recipient): ?string
    {
        return match ($recipient) {
            'client' => $this->resolveClientEmail(),
            'admin'  => config('mail.admin_address', 'admin@websiteexpert.co.uk'),
            default  => filter_var($recipient, FILTER_VALIDATE_EMAIL) ? $recipient : null,
        };
    }

    private function resolveRecipientPhone(string $recipient): ?string
    {
        return match ($recipient) {
            'client' => $this->resolveClientPhone(),
            default  => preg_match('/^\+?[\d\s\-()]{7,}$/', $recipient) ? $recipient : null,
        };
    }

    private function resolveClient(): ?Client
    {
        if (isset($this->context['client_id'])) {
            return Client::find($this->context['client_id']);
        }
        if (isset($this->context['lead_id'])) {
            return Lead::find($this->context['lead_id'])?->client;
        }
        if (isset($this->context['project_id'])) {
            return Project::find($this->context['project_id'])?->client;
        }
        if (isset($this->context['invoice_id'])) {
            return Invoice::find($this->context['invoice_id'])?->client;
        }
        if (isset($this->context['quote_id'])) {
            return Quote::find($this->context['quote_id'])?->client;
        }
        return null;
    }

    private function resolveClientPhone(): ?string
    {
        if (isset($this->context['client_id'])) {
            return Client::find($this->context['client_id'])?->phone;
        }
        if (isset($this->context['lead_id'])) {
            $lead = Lead::find($this->context['lead_id']);
            return $lead?->client?->phone ?? $lead?->phone;
        }
        if (isset($this->context['project_id'])) {
            return Project::find($this->context['project_id'])?->client?->phone;
        }
        if (isset($this->context['invoice_id'])) {
            return Invoice::find($this->context['invoice_id'])?->client?->phone;
        }
        if (isset($this->context['quote_id'])) {
            return Quote::find($this->context['quote_id'])?->client?->phone;
        }

        return null;
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
