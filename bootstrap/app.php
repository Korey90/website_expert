<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'has.business' => \App\Http\Middleware\EnsureHasBusiness::class,
            'api.token'    => \App\Http\Middleware\ApiTokenAuthentication::class,
            'landing-page.tenant' => \App\Http\Middleware\EnsureLandingPageTenantAccess::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'payu/notify',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Daily: flag leads with no activity in 7+ days → triggers lead.inactive automation
        $schedule->command('leads:check-stale')->dailyAt('08:00')->onOneServer();

        // Daily: remove raw IP addresses from lead_sources older than 30 days (GDPR)
        $schedule->job(new \App\Jobs\CleanLeadSourcePiiJob)->dailyAt('02:00')->withoutOverlapping();

        // Daily: remind clients about invoices due within 3 days → triggers invoice.due_soon automation
        $schedule->command('invoices:check-due-soon')->dailyAt('09:00')->onOneServer();

        // Daily: prune old automation logs based on retention setting (default 90 days)
        $schedule->command('automation:prune-logs')->dailyAt('03:00')->onOneServer();
    })
    ->create();
