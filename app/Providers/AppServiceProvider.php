<?php

namespace App\Providers;

use App\Listeners\AutomationEventListener;
use App\Listeners\ClientActivityListener;
use App\Livewire\CustomDatabaseNotifications;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Override Filament's DatabaseNotifications so X/close only marks as read, not deletes
        Livewire::component('database-notifications', CustomDatabaseNotifications::class);

        Event::subscribe(AutomationEventListener::class);
        Event::subscribe(ClientActivityListener::class);

        $this->applyIntegrationSettings();
    }

    /**
     * Override mail/SMS config at runtime from DB settings saved in IntegrationSettingsPage.
     * Falls back silently if DB is not yet available (e.g. during migrations).
     */
    private function applyIntegrationSettings(): void
    {
        try {
            $mailer = Setting::get('mail_mailer');
            if (! $mailer) {
                return; // Nothing saved yet — use .env defaults
            }

            Config::set('mail.default', $mailer);

            $host       = Setting::get('mail_host');
            $port       = Setting::get('mail_port');
            $encryption = Setting::get('mail_encryption');
            $username   = Setting::get('mail_username');
            $password   = Setting::get('mail_password');
            $from       = Setting::get('mail_from');
            $fromName   = Setting::get('mail_from_name');

            if ($host)       Config::set('mail.mailers.smtp.host',       $host);
            if ($port)       Config::set('mail.mailers.smtp.port',       (int) $port);
            if ($encryption !== null) Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
            if ($username)   Config::set('mail.mailers.smtp.username',   $username);
            if ($password)   Config::set('mail.mailers.smtp.password',   $password);
            if ($from)       Config::set('mail.from.address',            $from);
            if ($fromName)   Config::set('mail.from.name',               $fromName);

            // Apply to the named mailer that matches the chosen driver
            if (in_array($mailer, ['postmark', 'resend', 'mailgun', 'ses'], true)) {
                Config::set("mail.mailers.{$mailer}", array_merge(
                    config("mail.mailers.{$mailer}", []),
                    ['driver' => $mailer],
                ));
            }
        } catch (\Throwable) {
            // DB unavailable (e.g. first deploy) — silently fall back to .env
        }
    }
}
