<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\RecentLeadsWidget;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\ActiveProjectsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\LeadsBySourceWidget;
use App\Filament\Widgets\ProjectStatusWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('WebsiteExpert')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary'  => Color::hex('#ff2b17'),
                'gray'     => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                function (): \Illuminate\View\View {
                    $pinnedNotes = \App\Models\LeadNote::where('is_pinned', true)
                        ->with('lead')
                        ->orderByDesc('created_at')
                        ->limit(15)
                        ->get();
                    return view('filament.components.pinned-notes', compact('pinnedNotes'));
                },
            )
            ->navigationGroups([
                NavigationGroup::make('CRM'),
                NavigationGroup::make('Projects'),
                NavigationGroup::make('Finance'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Settings')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::BODY_END,
                function (): string {
                    $userId = auth()->id();
                    if (! $userId) {
                        return '';
                    }

                    return <<<JS
                    <script data-navigate-once>
                        (function () {
                            const _notifUserId = {$userId};

                            function playNotificationSound() {
                                try {
                                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                                    if (!AudioCtx) return;
                                    const ctx = new AudioCtx();
                                    const run = () => {
                                        const t = ctx.currentTime;

                                        function ggPing(freq, startTime, dur) {
                                            const osc  = ctx.createOscillator();
                                            const env  = ctx.createGain();
                                            const dist = ctx.createWaveShaper();

                                            // Slight triangle softness on top of sine = classic GG plastic tone
                                            osc.type = 'sine';
                                            osc.frequency.setValueAtTime(freq, startTime);
                                            // Quick downward bend — the GG "boing" fall
                                            osc.frequency.exponentialRampToValueAtTime(freq * 0.78, startTime + dur * 0.9);

                                            // Sharp attack, fast exponential decay
                                            env.gain.setValueAtTime(0.0001, startTime);
                                            env.gain.exponentialRampToValueAtTime(0.30, startTime + 0.008);
                                            env.gain.exponentialRampToValueAtTime(0.0001, startTime + dur);

                                            // Soft waveshaper for warmth
                                            const curve = new Float32Array(256);
                                            for (let i = 0; i < 256; i++) {
                                                const x = (i * 2) / 256 - 1;
                                                curve[i] = (Math.PI + 80) * x / (Math.PI + 80 * Math.abs(x));
                                            }
                                            dist.curve = curve;
                                            dist.oversample = '2x';

                                            osc.connect(dist);
                                            dist.connect(env);
                                            env.connect(ctx.destination);
                                            osc.start(startTime);
                                            osc.stop(startTime + dur + 0.02);
                                        }

                                        // Two pings — classic GG double-boing: higher then slightly lower
                                        ggPing(1350, t,        0.18);
                                        ggPing(1050, t + 0.21, 0.22);
                                    };
                                    ctx.state === 'suspended' ? ctx.resume().then(run) : run();
                                } catch (e) {}
                            }

                            function subscribeEcho() {
                                window.Echo
                                    .private('App.Models.User.' + _notifUserId)
                                    .listen('.database-notifications.sent', playNotificationSound);
                            }

                            window.addEventListener('EchoLoaded', subscribeEcho);
                            if (window.Echo) subscribeEcho();

                            window.addEventListener('notificationSent', playNotificationSound);

                            // ─── Helpers ───
                            function getDbNotifWire() {
                                var el = document.querySelector('.fi-no-database');
                                if (!el) return null;
                                var wireId = el.getAttribute('wire:id');
                                return wireId ? Livewire.find(wireId) : null;
                            }

                            // ─── View click: mark as read + navigate, NEVER delete ───
                            document.addEventListener('click', function(e) {
                                var link = e.target.closest('a[href]');
                                if (!link) return;
                                if (!link.closest('.fi-no-notification-unread-ctn, .fi-no-notification-read-ctn')) return;

                                var href = link.getAttribute('href') || '';
                                var idMatch = href.match(/[?&]id=([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i);
                                var toMatch = href.match(/[?&]to=([^&]+)/);

                                if (!idMatch || !toMatch) return;

                                var notifId = idMatch[1];
                                var destination = decodeURIComponent(toMatch[1]);

                                e.preventDefault();
                                e.stopPropagation();

                                var xsrfMatch = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
                                var token = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : '';

                                fetch('/notification-mark-read', {
                                    method: 'POST',
                                    keepalive: true,
                                    headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': token },
                                    body: JSON.stringify({ id: notifId }),
                                }).catch(function() {});

                                window.dispatchEvent(new CustomEvent('markedNotificationAsRead', { detail: { id: notifId } }));
                                window.location.href = destination;
                            }, true);

                            // ─── X button: DELETE notification from DB ───
                            document.addEventListener('click', function(e) {
                                var btn = e.target.closest('.fi-no-notification-close-btn');
                                if (!btn) return;
                                if (!btn.closest('.fi-no-database')) return; // only panel, not toast

                                var notifEl = btn.closest('[x-data]');
                                if (!notifEl) return;
                                var xData = notifEl.getAttribute('x-data') || '';
                                var m = xData.match(/"id"\s*:\s*"([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})"/i);
                                if (!m) return;

                                var wire = getDbNotifWire();
                                if (wire) wire.removeNotification(m[1]);
                            }, true);
                            // ─── END ───
                        })();
                    </script>
                    JS;
                },
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                QuickActionsWidget::class,
                StatsOverviewWidget::class,
                RecentLeadsWidget::class,
                OverdueInvoicesWidget::class,
                ActiveProjectsWidget::class,
                RevenueChartWidget::class,
                LeadsBySourceWidget::class,
                ProjectStatusWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

