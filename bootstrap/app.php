<?php

use App\Http\Middleware\ApiTokenAuthentication;
use App\Http\Middleware\DetectGeoCurrency;
use App\Http\Middleware\EnsureHasBusiness;
use App\Http\Middleware\EnsureLandingPageTenantAccess;
use App\Http\Middleware\EnsurePortalClientAccess;
use App\Http\Middleware\EnsurePortalWorkspaceAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\CheckDomainExpiryJob;
use App\Jobs\CleanLeadSourcePiiJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

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
            'has.business' => EnsureHasBusiness::class,
            'api.token' => ApiTokenAuthentication::class,
            'landing-page.tenant' => EnsureLandingPageTenantAccess::class,
            'portal.client' => EnsurePortalClientAccess::class,
            'portal.workspace' => EnsurePortalWorkspaceAccess::class,
        ]);

        // geo_currency cookie stores only a plain-text currency code (GBP / EUR / PLN).
        // Excluding it from encryption simplifies testing and avoids decryption issues.
        $middleware->encryptCookies(except: [
            DetectGeoCurrency::COOKIE_NAME,
        ]);

        $middleware->web(append: [
            DetectGeoCurrency::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'payu/notify',
            'webhooks/domain/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Daily: flag leads with no activity in 7+ days → triggers lead.inactive automation
        $schedule->command('leads:check-stale')->dailyAt('08:00')->onOneServer();

        // Daily: remove raw IP addresses from lead_sources older than 30 days (GDPR)
        $schedule->job(new CleanLeadSourcePiiJob)->dailyAt('02:00')->withoutOverlapping();

        // Daily: remind clients about invoices due within 3 days → triggers invoice.due_soon automation
        $schedule->command('invoices:check-due-soon')->dailyAt('09:00')->onOneServer();

        // Daily: prune old automation logs based on retention setting (default 90 days)
        $schedule->command('automation:prune-logs')->dailyAt('03:00')->onOneServer();

        // Daily: check domain expiry and dispatch renewal reminder emails (30/14/7/1 days)
        $schedule->job(new CheckDomainExpiryJob)->dailyAt('08:30')->withoutOverlapping()->onOneServer();
    })
    ->create();
