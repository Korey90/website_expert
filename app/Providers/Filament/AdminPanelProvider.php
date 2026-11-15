<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AccountProfilePage;
use App\Filament\Pages\AdminDashboard;
use App\Filament\Support\AdminGlobalSearchProvider;
use App\Filament\Support\AdminNavigation;
use App\Models\LeadNote;
use App\Support\PermissionHelper;
use Filament\Actions\Action;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

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
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName('Website Expert NI'),
            ])
            ->brandName('Website Expert NI')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#f04b37'),
                'gray' => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->globalSearch(AdminGlobalSearchProvider::class)
            ->globalSearchDebounce('300ms')
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->navigation(
                fn (NavigationBuilder $builder): NavigationBuilder => AdminNavigation::build($builder),
            )
            ->userMenuItems([
                'profile' => fn (): Action => Action::make('profile')
                    ->label(__('admin.account.profile'))
                    ->icon('heroicon-o-user-circle')
                    ->url(AccountProfilePage::getUrl())
                    ->visible(AccountProfilePage::canAccess()),
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                function (): View|HtmlString {
                    $user = auth()->user();
                    $business = currentBusiness();

                    if (! $user || ! $business) {
                        return new HtmlString('');
                    }

                    $pinnedNotes = collect();

                    if (PermissionHelper::allows($user, 'view_leads')) {
                        $pinnedNotes = LeadNote::where('is_pinned', true)
                            ->whereHas('lead', fn ($query) => $query->where('business_id', $business->id))
                            ->with(['lead', 'user'])
                            ->orderByDesc('created_at')
                            ->limit(15)
                            ->get();
                    }

                    return view('filament.components.topbar-actions', [
                        'canViewPinnedNotes' => PermissionHelper::allows($user, 'view_leads'),
                        'pinnedNotes' => $pinnedNotes,
                    ]);
                },
            )
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
                    $src = Vite::asset('resources/js/admin/notifications.js');
                    $fraudContext = view('filament.components.hmrc-fraud-context')->render();
                    $navigationState = view('filament.components.navigation-state')->render();

                    return new HtmlString(
                        "<script data-navigate-once>window.AdminPanelConfig = {$config};</script>".
                        "<script data-navigate-once src=\"{$src}\"></script>".
                        $fraudContext.
                        $navigationState
                    );
                },
            )
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
