<?php

namespace App\Services\Billing;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\LpGenerationRateLimit;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
        'free'   => ['landing_pages' => 1,           'ai_per_month' => 3,           'name' => 'Free'],
        'basic'  => ['landing_pages' => 5,           'ai_per_month' => 10,          'name' => 'Basic'],
        'pro'    => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => 50,          'name' => 'Pro'],
        'agency' => ['landing_pages' => PHP_INT_MAX, 'ai_per_month' => PHP_INT_MAX, 'name' => 'Agency'],
    ];

    /**
     * Load plans from DB (cached 60 min). Falls back to PLANS constant on failure.
     *
     * @return array<string, array{name: string, landing_pages: int, ai_per_month: int}>
     */
    public static function getPlans(): array
    {
        try {
            return Cache::remember('saas_plans', 3600, function () {
                return Plan::where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->keyBy('slug')
                    ->map(fn (Plan $plan) => [
                        'name'          => $plan->name,
                        'landing_pages' => $plan->max_landing_pages ?? PHP_INT_MAX,
                        'ai_per_month'  => $plan->max_ai_per_month  ?? PHP_INT_MAX,
                        'price'         => $plan->price_monthly,
                    ])
                    ->toArray();
            });
        } catch (\Throwable) {
            return self::PLANS;
        }
    }

    /** Clear the cached plan list (call after PlanResource saves). */
    public static function clearCache(): void
    {
        Cache::forget('saas_plans');
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
        $plan  = $this->getEffectivePlan($business);
        $plans = self::getPlans();

        return $plans[$plan]['landing_pages'] ?? 1;
    }

    public function getAiGenerationLimit(Business $business): int
    {
        $plan  = $this->getEffectivePlan($business);
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
                'year'        => $now->year,
                'month'       => $now->month,
                'count'       => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            uniqueBy: ['business_id', 'year', 'month'],
            update: ['count' => \Illuminate\Support\Facades\DB::raw('count + 1'), 'updated_at' => $now],
        );
    }
}
