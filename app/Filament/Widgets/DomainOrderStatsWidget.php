<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use App\Models\DomainOrder;
use App\Scopes\BusinessScope;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DomainOrderStatsWidget extends BaseWidget
{
    protected static ?int $sort = 10;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalOrders = DomainOrder::withoutGlobalScope(BusinessScope::class)->count();

        $pendingOrders = DomainOrder::withoutGlobalScope(BusinessScope::class)
            ->whereIn('status', ['pending_payment', 'paid', 'registering'])
            ->count();

        $completedDomains = Domain::withoutGlobalScope(BusinessScope::class)
            ->where('status', 'active')
            ->count();

        $monthRevenue = DomainOrder::withoutGlobalScope(BusinessScope::class)
            ->where('status', 'completed')
            ->whereMonth('completed_at', now()->month)
            ->whereYear('completed_at', now()->year)
            ->sum('retail_price');

        $expiringSoon = Domain::withoutGlobalScope(BusinessScope::class)
            ->where('status', 'active')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->count();

        return [
            Stat::make('Total Orders', $totalOrders)
                ->description('All domain orders')
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting payment or registration')
                ->icon('heroicon-o-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make('Active Domains', $completedDomains)
                ->description('Registered & active')
                ->icon('heroicon-o-globe-alt')
                ->color('success'),

            Stat::make('Revenue This Month', '£' . number_format((float) $monthRevenue, 2))
                ->description('Completed orders in ' . now()->format('F'))
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Expiring in 30 Days', $expiringSoon)
                ->description('Domains needing renewal')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($expiringSoon > 0 ? 'danger' : 'success'),
        ];
    }
}
