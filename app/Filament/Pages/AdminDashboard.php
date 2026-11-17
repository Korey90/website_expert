<?php

namespace App\Filament\Pages;

use App\Filament\Support\FilamentPermissionRegistry;
use App\Filament\Widgets\LeadsBySourceWidget;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\ProjectDeadlinesWidget;
use App\Filament\Widgets\ProjectStatusWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentLeadsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\SaasMetricsWidget;
use App\Filament\Widgets\StaleLeadsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Support\PermissionHelper;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class AdminDashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        $permission = FilamentPermissionRegistry::pagePermission(static::class);

        return filled($permission)
            && PermissionHelper::allows(auth()->user(), $permission)
            && parent::canAccess();
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    // WIDGETY GŁÓWNE
    public function getWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            StatsOverviewWidget::class,
            SaasMetricsWidget::class,
            RevenueChartWidget::class,
            LeadsBySourceWidget::class,
            ProjectStatusWidget::class,
        ];
    }

    // DASHBOARD CONTENT
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            // Zachowanie filtrów dashboardu, jeśli istnieją
            ...(method_exists($this, 'getFiltersForm')
                ? [$this->getFiltersFormContentComponent()]
                : []),

            // Pierwsze 6 widgetów
            $this->getWidgetsContentComponent(),

            // Ostatnie 4 widgety w zakładkach
            Tabs::make('Dashboard Details')
                ->tabs([
                    $this->makeWidgetTab(
                        'Overdue Invoices',
                        OverdueInvoicesWidget::class,
                        'heroicon-o-banknotes',
                    ),

                    $this->makeWidgetTab(
                        'Recent Leads',
                        RecentLeadsWidget::class,
                        'heroicon-o-user-group',
                    ),

                    $this->makeWidgetTab(
                        'Project Deadlines',
                        ProjectDeadlinesWidget::class,
                        'heroicon-o-calendar-days',
                    ),

                    $this->makeWidgetTab(
                        'Stale Leads',
                        StaleLeadsWidget::class,
                        'heroicon-o-clock',
                    ),
                ])
                ->contained(false)
                ->persistTabInQueryString(),
        ]);
    }

    // POMOCNICZA METODA DO GENEROWANIA ZAKŁADEK
    private function makeWidgetTab(
        string $label,
        string $widget,
        string $icon,
    ): Tab {
        return Tab::make($label)
            ->icon($icon)
            ->schema([
                Grid::make(1)
                    ->schema(fn (): array =>
                        $this->getWidgetsSchemaComponents([$widget])
                    ),
            ]);
    }
}