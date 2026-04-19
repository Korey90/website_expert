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
use Illuminate\Support\Facades\Vite;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\SaasMetricsWidget;
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
use Illuminate\Support\HtmlString;
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
                NavigationGroup::make('Sales'),
                NavigationGroup::make('Projects'),
                NavigationGroup::make('Finance'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Automation'),
                NavigationGroup::make('Reports')->collapsed(),
                NavigationGroup::make('SaaS Billing')->collapsed(),
                NavigationGroup::make('Settings')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->databaseNotifications()
            ->renderHook(
                PanelsRenderHook::BODY_END,
                function (): HtmlString {
                    $userId = auth()->id();
                    if (! $userId) {
                        return new HtmlString('');
                    }

                    $config = json_encode(['userId' => $userId]);
                    $src    = Vite::asset('resources/js/admin/notifications.js');

                    return new HtmlString(
                        "<script data-navigate-once>window.AdminPanelConfig = {$config};</script>" .
                        "<script data-navigate-once src=\"{$src}\"></script>"
                    );
                },
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                QuickActionsWidget::class,
                SaasMetricsWidget::class,
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

