<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Services\Billing\PlanService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SaasMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // ── Active subscriptions ──────────────────────────────────────────────
        $activeQuery = Business::where('stripe_subscription_status', 'active');
        $activeCount = (clone $activeQuery)->count();

        // ── MRR calculation ───────────────────────────────────────────────────
        // Group active subscribers by plan, multiply by monthly price
        $planPrices = [
            'pro'    => 49,
            'agency' => 149,
        ];

        $mrr = 0;
        foreach ($planPrices as $plan => $price) {
            $planCount = (clone $activeQuery)->where('plan', $plan)->count();
            $mrr += $planCount * $price;
        }

        // ── Churn (canceled last 30 days) ─────────────────────────────────────
        $churnCount = Business::where('stripe_subscription_status', 'canceled')
            ->where('updated_at', '>=', Carbon::now()->subDays(30))
            ->count();

        // ── Trialing ──────────────────────────────────────────────────────────
        $trialingCount = Business::where('stripe_subscription_status', 'trialing')
            ->orWhere(fn ($q) => $q->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', Carbon::now()))
            ->count();

        // ── Past due ──────────────────────────────────────────────────────────
        $pastDueCount = Business::where('stripe_subscription_status', 'past_due')->count();

        // ── Plan distribution description ─────────────────────────────────────
        $planBreakdown = collect($planPrices)
            ->map(fn ($price, $plan) => ucfirst($plan) . ': ' . (clone $activeQuery)->where('plan', $plan)->count())
            ->implode(' | ');

        return [
            Stat::make('MRR', '£' . number_format($mrr, 0))
                ->description($planBreakdown ?: 'No paid subscribers')
                ->icon('heroicon-o-banknotes')
                ->color($mrr > 0 ? 'success' : 'gray'),

            Stat::make('Active Subscribers', $activeCount)
                ->description('Paid active subscriptions')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Trialing', $trialingCount)
                ->description('In free trial')
                ->icon('heroicon-o-clock')
                ->color('info'),

            Stat::make('Churn (30d)', $churnCount)
                ->description('Canceled in last 30 days')
                ->icon('heroicon-o-arrow-trending-down')
                ->color($churnCount > 0 ? 'danger' : 'success'),

            Stat::make('Past Due', $pastDueCount)
                ->description('Payment failed — require attention')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($pastDueCount > 0 ? 'warning' : 'success'),
        ];
    }
}
