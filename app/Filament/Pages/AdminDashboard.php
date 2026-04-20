<?php

namespace App\Filament\Pages;

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
use Filament\Pages\Dashboard as BaseDashboard;

class AdminDashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -2;

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            StatsOverviewWidget::class,
            SaasMetricsWidget::class,
            RevenueChartWidget::class,
            LeadsBySourceWidget::class,
            ProjectStatusWidget::class,
            OverdueInvoicesWidget::class,
            RecentLeadsWidget::class,
            ProjectDeadlinesWidget::class,
            StaleLeadsWidget::class,
        ];
    }
}
