<?php

namespace App\Filament\Pages;

use App\Filament\Support\FilamentPermissionRegistry;
use App\Filament\Widgets\LeadsBySourceWidget;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\ProjectDeadlinesWidget;
use App\Filament\Widgets\ProjectStatusWidget;
use App\Filament\Widgets\RecentLeadsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\StaleLeadsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Support\PermissionHelper;
use Filament\Pages\Dashboard as BaseDashboard;

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

    public function getTitle(): string
    {
        return __('admin.dashboard.title');
    }

    public function getSubheading(): ?string
    {
        $businessName = currentBusiness()?->name;

        return $businessName
            ? __('admin.dashboard.subheading', ['business' => $businessName])
            : __('admin.dashboard.subheading_fallback');
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
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
