<?php

namespace App\Automation\Actions;

use App\Automation\ActionSkippedException;
use App\Mail\TemplatedMailable;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\User;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sends a bell notification + email to all admin/manager users.
 *
 * Action config keys:
 *   message      (string, required) — notification body, supports {{vars}}
 *   template_slug (string, optional) — EmailTemplate slug for email; falls back to raw message
 *   roles        (array, optional)  — defaults to ['admin', 'manager']
 *   url          (string, optional) — CTA link in bell notification
 */
class NotifyTeamAction extends BaseAutomationAction
{
    public function execute(array $action, array $context, string $triggerEvent): void
    {
        $vars    = array_merge($context, $this->buildTemplateVars($context));
        $message = $this->interpolate($action['message'] ?? 'Automation event: ' . $triggerEvent, $vars);
        $roles   = $action['roles'] ?? ['admin', 'manager', 'super_admin'];
        $url     = $this->interpolate($action['url'] ?? '', $vars);

        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', (array) $roles))
            ->where('is_active', true)
            ->get();

        if ($users->isEmpty()) {
            throw new ActionSkippedException('No active users found with roles: ' . implode(', ', (array) $roles));
        }

        // Resolve lead for CTA url fallback
        if (! $url && isset($context['lead_id'])) {
            $url = route('filament.admin.resources.leads.view', ['record' => $context['lead_id']]);
        }

        // Resolve email template
        $emailTemplate   = null;
        $templateSlug    = $action['template_slug'] ?? null;
        if ($templateSlug) {
            $emailTemplate = EmailTemplate::where('slug', $templateSlug)->where('is_active', true)->first();
        }

        $lead = isset($context['lead_id']) ? Lead::with('client')->find($context['lead_id']) : null;

        foreach ($users as $user) {
            // ── Bell notification ─────────────────────────────────────────
            $notifId = (string) Str::orderedUuid();

            $notification = FilamentNotification::make()
                ->title($this->interpolate($action['title'] ?? 'Team Notification', $vars))
                ->body($message)
                ->icon($action['icon'] ?? 'heroicon-o-bell')
                ->iconColor($action['color'] ?? 'info');

            if ($url) {
                $followUrl = route('notification.follow', ['to' => $url, 'id' => $notifId]);
                $notification->actions([
                    NotificationAction::make('view')->label('View')->url($followUrl),
                ]);
            }

            $data             = $notification->toArray();
            $data['format']   = 'filament';
            $data['duration'] = 'persistent';
            unset($data['id']);

            $user->notifications()->create([
                'id'      => $notifId,
                'type'    => \Filament\Notifications\DatabaseNotification::class,
                'data'    => $data,
                'read_at' => null,
            ]);

            DatabaseNotificationsSent::dispatch($user);

            // ── Email ─────────────────────────────────────────────────────
            if ($emailTemplate) {
                $locale  = $user->locale ?? app()->getLocale();
                $content = $emailTemplate->getForLocale($locale);

                $emailVars = array_merge($vars, [
                    'client_name' => $lead?->client?->primary_contact_name
                        ?? $lead?->client?->primary_contact_email
                        ?? ($vars['client_name'] ?? 'Unknown'),
                    'lead_name'   => $lead?->title ?? ($vars['lead_title'] ?? ''),
                    'lead_source' => $lead?->source ?? ($context['source'] ?? ''),
                    'lead_id'     => (string) ($context['lead_id'] ?? ''),
                    'lead_url'    => $url,
                ]);

                $subject = $this->interpolate($content['subject'] ?? '', $emailVars);
                $body    = $this->interpolate($content['body_html'] ?? '', $emailVars);

                if ($subject && $body) {
                    Mail::to($user->email)->send(new TemplatedMailable($subject, $body));
                }
            } elseif (! empty($user->email)) {
                // Fallback: raw message as email body (already in queued job — send synchronously)
                Mail::raw($message, fn ($msg) => $msg
                    ->to($user->email)
                    ->subject($action['title'] ?? 'Team Notification — WebsiteExpert')
                );
            }
        }
    }
}
