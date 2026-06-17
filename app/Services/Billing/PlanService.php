<?php

namespace App\Services\Billing;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\LpGenerationRateLimit;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\Currency\CurrencyResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PlanService — enforces SaaS plan limits and provides gate checks.
 *
 * Plans are defined in the `plans` DB table (managed via Filament PlanResource).
 * The PLANS constant acts as a fallback when the DB table is not yet available.
 */
class PlanService
{
    /** Fallback constant — used when plans table doesn't exist yet. */
    public const PLANS = [
        'free' => ['landing_pages' => 1,           'ai_per_month' => 3,           'name' => 'Free',   'price' => 0],
        'basic' => ['landing_pages' => 5,           'ai_per_month' => 10,          'name' => 'Basic',  'price' => 0],
        'pro' => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => 50,          'name' => 'Pro',    'price' => 0],
        'agency' => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => PHP_INT_MAX, 'name' => 'Agency', 'price' => 0],
    ];

    /**
     * Load plans from DB (cached 60 min). Falls back to PLANS constant on failure.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getPlans(?string $currency = null): array
    {
        $currency = self::resolveCurrency($currency);
        $cacheKey = 'saas_plans_'.strtolower($currency);

        try {
            return Cache::remember($cacheKey, 3600, function () use ($currency) {
                return Plan::where('is_active', true)
                    ->with('planPrices')
                    ->orderBy('sort_order')
                    ->get()
                    ->keyBy('slug')
                    ->map(fn (Plan $plan) => self::planPayload($plan, $currency))
                    ->toArray();
            });
        } catch (\Throwable) {
            return self::fallbackPlans($currency);
        }
    }

    /** Clear the cached plan list (call after PlanResource saves). */
    public static function clearCache(): void
    {
        Cache::forget('saas_plans');

        foreach (app(CurrencyResolver::class)->supportedCurrencies() as $currency => $meta) {
            Cache::forget('saas_plans_'.strtolower($currency));
        }
    }

    public static function resolveCurrency(?string $currency = null): string
    {
        $resolver = app(CurrencyResolver::class);

        if ($currency !== null) {
            return $resolver->normalize($currency);
        }

        return $resolver->resolve(request());
    }

    public static function getCheckoutPrice(string $planSlug, ?string $currency = null, string $interval = PlanPrice::INTERVAL_MONTHLY): ?PlanPrice
    {
        $plan = Plan::where('slug', $planSlug)
            ->where('is_active', true)
            ->with('planPrices')
            ->first();

        if (! $plan) {
            return null;
        }

        return self::priceForPlan($plan, self::resolveCurrency($currency), $interval);
    }

    public static function priceForPlan(Plan $plan, ?string $currency = null, string $interval = PlanPrice::INTERVAL_MONTHLY): ?PlanPrice
    {
        $targetCurrency = self::resolveCurrency($currency);
        $defaultCurrency = app(CurrencyResolver::class)->defaultCurrency();
        $interval = strtolower(trim($interval));

        $prices = $plan->relationLoaded('planPrices')
            ? $plan->planPrices
                ->where('is_active', true)
                ->where('interval', $interval)
            : $plan->planPrices()
                ->active()
                ->where('interval', $interval)
                ->get();

        return $prices->firstWhere('currency', $targetCurrency)
            ?? $prices->firstWhere('currency', $defaultCurrency)
            ?? $prices->sortBy('currency')->first();
    }

    public static function stripePriceIdFor(PlanPrice $price): ?string
    {
        if (filled($price->stripe_price_id)) {
            return $price->stripe_price_id;
        }

        $planSlug = $price->plan?->slug;
        if (! $planSlug) {
            return null;
        }

        $configured = config("services.stripe.prices.{$planSlug}.{$price->currency}.{$price->interval}");
        if (filled($configured)) {
            return (string) $configured;
        }

        if ($price->currency === app(CurrencyResolver::class)->defaultCurrency()
            && $price->interval === PlanPrice::INTERVAL_MONTHLY
        ) {
            return match ($planSlug) {
                'pro' => config('services.stripe.price_pro_monthly'),
                'agency' => config('services.stripe.price_agency_monthly'),
                default => null,
            };
        }

        return null;
    }

    public static function findPlanSlugByStripePriceId(string $priceId): ?string
    {
        $price = self::findPlanPriceByStripePriceId($priceId);

        if ($price?->plan) {
            return $price->plan->slug;
        }

        $legacyPlan = Plan::query()
            ->where('stripe_price_id_monthly', $priceId)
            ->orWhere('stripe_price_id_yearly', $priceId)
            ->first();

        if ($legacyPlan) {
            return $legacyPlan->slug;
        }

        foreach ((array) config('services.stripe.prices', []) as $slug => $currencyRows) {
            foreach ((array) $currencyRows as $intervalRows) {
                foreach ((array) $intervalRows as $configuredPriceId) {
                    if ($configuredPriceId === $priceId) {
                        return (string) $slug;
                    }
                }
            }
        }

        return match ($priceId) {
            config('services.stripe.price_pro_monthly') => 'pro',
            config('services.stripe.price_agency_monthly') => 'agency',
            default => null,
        };
    }

    public static function findPlanPriceByStripePriceId(string $priceId): ?PlanPrice
    {
        $price = PlanPrice::query()
            ->where('stripe_price_id', $priceId)
            ->with('plan')
            ->first();

        if ($price) {
            return $price;
        }

        foreach ((array) config('services.stripe.prices', []) as $slug => $currencyRows) {
            foreach ((array) $currencyRows as $currency => $intervalRows) {
                foreach ((array) $intervalRows as $interval => $configuredPriceId) {
                    if ($configuredPriceId !== $priceId) {
                        continue;
                    }

                    return PlanPrice::query()
                        ->whereHas('plan', fn ($query) => $query->where('slug', $slug))
                        ->where('currency', strtoupper((string) $currency))
                        ->where('interval', strtolower((string) $interval))
                        ->with('plan')
                        ->first();
                }
            }
        }

        return null;
    }

    // ── Plan resolution ───────────────────────────────────────────────────────

    public function getEffectivePlan(Business $business): string
    {
        // Trial period → grant access as if on pro plan
        if ($this->isOnTrial($business)) {
            return 'pro';
        }

        return in_array($business->plan, array_keys(self::getPlans()))
            ? $business->plan
            : 'free';
    }

    public function isOnTrial(Business $business): bool
    {
        return $business->trial_ends_at !== null
            && Carbon::now()->lt($business->trial_ends_at);
    }

    public function getTrialDaysRemaining(Business $business): int
    {
        if (! $this->isOnTrial($business)) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($business->trial_ends_at, absolute: true);
    }

    // ── Plan limits ───────────────────────────────────────────────────────────

    public function getLandingPageLimit(Business $business): int
    {
        $plan = $this->getEffectivePlan($business);
        $plans = self::getPlans();

        return $plans[$plan]['landing_pages'] ?? 1;
    }

    public function getAiGenerationLimit(Business $business): int
    {
        $plan = $this->getEffectivePlan($business);
        $plans = self::getPlans();

        return $plans[$plan]['ai_per_month'] ?? 3;
    }

    // ── Gate checks ───────────────────────────────────────────────────────────

    public function canCreateLandingPage(Business $business): bool
    {
        $limit = $this->getLandingPageLimit($business);

        if ($limit === PHP_INT_MAX) {
            return true;
        }

        $current = LandingPage::where('business_id', $business->id)
            ->whereIn('status', ['draft', 'published'])
            ->count();

        return $current < $limit;
    }

    public function canUseAiGenerator(Business $business): bool
    {
        $limit = $this->getAiGenerationLimit($business);

        if ($limit === PHP_INT_MAX) {
            return true;
        }

        return $this->getCurrentMonthAiCount($business) < $limit;
    }

    public function getCurrentLandingPageCount(Business $business): int
    {
        return LandingPage::where('business_id', $business->id)
            ->whereIn('status', ['draft', 'published'])
            ->count();
    }

    // ── AI rate limit ─────────────────────────────────────────────────────────

    public function getCurrentMonthAiCount(Business $business): int
    {
        $now = Carbon::now();

        return LpGenerationRateLimit::where('business_id', $business->id)
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->value('count') ?? 0;
    }

    public function getRemainingAiGenerations(Business $business): int
    {
        $limit = $this->getAiGenerationLimit($business);

        if ($limit === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->getCurrentMonthAiCount($business));
    }

    public function incrementAiCount(Business $business): void
    {
        $now = Carbon::now();

        LpGenerationRateLimit::upsert(
            [
                'business_id' => $business->id,
                'year' => $now->year,
                'month' => $now->month,
                'count' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            uniqueBy: ['business_id', 'year', 'month'],
            update: ['count' => DB::raw('count + 1'), 'updated_at' => $now],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function planPayload(Plan $plan, string $currency): array
    {
        $monthly = self::priceForPlan($plan, $currency, PlanPrice::INTERVAL_MONTHLY);
        $yearly = self::priceForPlan($plan, $currency, PlanPrice::INTERVAL_YEARLY);
        $resolvedCurrency = $monthly?->currency ?? $yearly?->currency ?? $currency;

        return [
            'name' => $plan->name,
            'landing_pages' => $plan->max_landing_pages ?? PHP_INT_MAX,
            'ai_per_month' => $plan->max_ai_per_month ?? PHP_INT_MAX,
            'price' => $monthly?->amount_minor ?? (int) $plan->price_monthly,
            'price_monthly' => $monthly?->amount_minor ?? (int) $plan->price_monthly,
            'price_yearly' => $yearly?->amount_minor ?? (int) $plan->price_yearly,
            'currency' => $resolvedCurrency,
            'stripe_price_id_monthly' => $monthly ? self::stripePriceIdFor($monthly) : $plan->stripe_price_id_monthly,
            'stripe_price_id_yearly' => $yearly ? self::stripePriceIdFor($yearly) : $plan->stripe_price_id_yearly,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function fallbackPlans(string $currency): array
    {
        return collect(self::PLANS)
            ->map(fn (array $plan) => $plan + [
                'price_monthly' => $plan['price'] ?? 0,
                'price_yearly' => 0,
                'currency' => $currency,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
            ])
            ->all();
    }
}
