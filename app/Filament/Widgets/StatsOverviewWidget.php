<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $activeProjects = Project::where('status', 'active')->count();
        $newLeads       = Lead::whereNull('deleted_at')->whereMonth('created_at', now()->month)->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        $monthRevenue   = Invoice::where('status', 'paid')
            ->whereMonth('updated_at', now()->month)
            ->sum('total');

        return [
            Stat::make('Active Projects', $activeProjects)
                ->description('Currently in progress')
                ->icon('heroicon-o-briefcase')
                ->color('primary'),

            Stat::make('New Leads This Month', $newLeads)
                ->description('Leads created in ' . now()->format('F'))
                ->icon('heroicon-o-funnel')
                ->color('info'),

            Stat::make('Overdue Invoices', $overdueInvoices)
                ->description('Require immediate attention')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($overdueInvoices > 0 ? 'danger' : 'success'),

            Stat::make('Revenue This Month', '£' . number_format($monthRevenue, 2))
                ->description('Paid invoices in ' . now()->format('F'))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
