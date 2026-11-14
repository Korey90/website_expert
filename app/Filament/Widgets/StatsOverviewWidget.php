<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use App\Services\Currency\CurrencySummaryFormatter;
use App\Support\PermissionHelper;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return PermissionHelper::allows($user, 'view_projects')
            || PermissionHelper::allows($user, 'view_leads')
            || PermissionHelper::allows($user, 'view_invoices');
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if (PermissionHelper::allows($user, 'view_projects')) {
            $activeProjects = Project::where('status', 'active')->count();
            $stats[] = Stat::make(__('admin.dashboard.stats.active_projects.label'), $activeProjects)
                ->description(__('admin.dashboard.stats.active_projects.description'))
                ->icon('heroicon-o-briefcase')
                ->color('primary');
        }

        if (PermissionHelper::allows($user, 'view_leads')) {
            $newLeads = Lead::whereNull('deleted_at')->whereMonth('created_at', now()->month)->count();
            $stats[] = Stat::make(__('admin.dashboard.stats.new_leads.label'), $newLeads)
                ->description(__('admin.dashboard.stats.new_leads.description', [
                    'month' => now()->translatedFormat('F'),
                ]))
                ->icon('heroicon-o-funnel')
                ->color('info');
        }

        if (PermissionHelper::allows($user, 'view_invoices')) {
            $overdueInvoices = Invoice::where('status', 'overdue')->count();
            $money = app(CurrencySummaryFormatter::class);
            $monthRevenue = $money->sumByCurrency(Invoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->get(['currency', 'total']));

            $stats[] = Stat::make(__('admin.dashboard.stats.overdue_invoices.label'), $overdueInvoices)
                ->description(__('admin.dashboard.stats.overdue_invoices.description'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overdueInvoices > 0 ? 'danger' : 'success');

            $stats[] = Stat::make(__('admin.dashboard.stats.monthly_revenue.label'), $money->formatGrouped($monthRevenue))
                ->description(__('admin.dashboard.stats.monthly_revenue.description', [
                    'month' => now()->translatedFormat('F'),
                ]))
                ->icon('heroicon-o-banknotes')
                ->color('success');
        }

        return $stats;
    }
}
