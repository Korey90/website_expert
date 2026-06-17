<?php

namespace App\Filament\Widgets;

use App\Models\Business;
use App\Models\PlanPrice;
use App\Services\Billing\PlanService;
use App\Services\Currency\CurrencyResolver;
use App\Services\Currency\CurrencySummaryFormatter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SaasMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $money = app(CurrencySummaryFormatter::class);

        // ── Active subscriptions ──────────────────────────────────────────────
        $activeBusinesses = Business::with('planPrice.plan')
            ->where('stripe_subscription_status', 'active')
            ->get();
        $activeCount = $activeBusinesses->count();

        // ── MRR calculation ───────────────────────────────────────────────────
        $mrr = $this->monthlyRecurringRevenueByCurrency($activeBusinesses);
        $arr = $mrr->map(fn (float $amount): float => $amount * 12);

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
        $planBreakdown = $activeBusinesses
            ->groupBy(fn (Business $business): string => $business->plan ?: 'unknown')
            ->map(fn (Collection $businesses, string $plan): string => ucfirst($plan).': '.$businesses->count())
            ->implode(' | ');

        return [
            Stat::make('MRR', $money->formatGrouped($mrr))
                ->description($planBreakdown ?: 'No paid subscribers')
                ->icon('heroicon-o-banknotes')
                ->color($mrr->sum() > 0 ? 'success' : 'gray'),

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

            Stat::make('ARR', $money->formatGrouped($arr))
                ->description('Annual Recurring Revenue')
                ->icon('heroicon-o-arrow-trending-up')
                ->color($mrr->sum() > 0 ? 'success' : 'gray'),
        ];
    }

    /**
     * @param  Collection<int, Business>  $businesses
     * @return Collection<string, float>
     */
    private function monthlyRecurringRevenueByCurrency(Collection $businesses): Collection
    {
        $resolver = app(CurrencyResolver::class);

        return $businesses
            ->reduce(function (Collection $totals, Business $business) use ($resolver): Collection {
                $price = $this->subscriptionPriceForBusiness($business);

                if (! $price || $price->amount_minor <= 0) {
                    return $totals;
                }

                $currency = $resolver->normalize($price->currency ?? $business->stripe_subscription_currency);
                $minorUnit = (int) ($resolver->metadata($currency)['minor_unit'] ?? 100);
                $monthlyAmount = $price->amount_minor / max(1, $minorUnit);

                if ($price->interval === PlanPrice::INTERVAL_YEARLY) {
                    $monthlyAmount /= 12;
                }

                $totals[$currency] = (float) ($totals[$currency] ?? 0) + $monthlyAmount;

                return $totals;
            }, collect())
            ->filter(fn (float $amount): bool => abs($amount) > 0.00001)
            ->sortKeys();
    }

    private function subscriptionPriceForBusiness(Business $business): ?PlanPrice
    {
        if ($business->planPrice) {
            return $business->planPrice;
        }

        return PlanService::getCheckoutPrice(
            $business->plan,
            $business->stripe_subscription_currency,
            $business->stripe_subscription_interval ?: PlanPrice::INTERVAL_MONTHLY,
        );
    }
}
