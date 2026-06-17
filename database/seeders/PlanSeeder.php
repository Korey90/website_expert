<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\Billing\PlanService;
use App\Services\Currency\CurrencyPriceCalculator;
use App\Services\Currency\CurrencyResolver;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = app(CurrencyPriceCalculator::class);
        $resolver = app(CurrencyResolver::class);
        $currencyRates = [
            'GBP' => 1.00,
            'EUR' => 1.18,
            'PLN' => 4.93,
        ];

        $plans = [
            [
                'slug' => 'free',
                'name' => 'Free',
                'description' => 'Get started — no credit card required.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'max_landing_pages' => 1,
                'max_ai_per_month' => 3,
                'multi_user' => false,
                'custom_domain' => false,
                'ab_testing' => false,
                'features' => ['1 Landing Page', '3 AI generations/month', 'Basic analytics'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'basic',
                'name' => 'Basic',
                'description' => 'For freelancers getting started.',
                'price_monthly' => 2900,   // GBP 29.00
                'price_yearly' => 27000,  // GBP 270.00 (~GBP 22.50/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'max_landing_pages' => 5,
                'max_ai_per_month' => 10,
                'multi_user' => false,
                'custom_domain' => false,
                'ab_testing' => false,
                'features' => ['5 Landing Pages', '10 AI generations/month', 'Lead management', 'Email notifications'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'For professionals who want to scale.',
                'price_monthly' => 4900,   // GBP 49.00
                'price_yearly' => 46800,  // GBP 468.00 (~GBP 39/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'max_landing_pages' => null,   // unlimited
                'max_ai_per_month' => 50,
                'multi_user' => false,
                'custom_domain' => true,
                'ab_testing' => true,
                'features' => ['Unlimited Landing Pages', '50 AI generations/month', 'A/B Testing', 'Custom Domain', 'Advanced analytics'],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'agency',
                'name' => 'Agency',
                'description' => 'For agencies managing multiple clients.',
                'price_monthly' => 14900,  // GBP 149.00
                'price_yearly' => 143000, // GBP 1430.00 (~GBP 119/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'max_landing_pages' => null,   // unlimited
                'max_ai_per_month' => null,   // unlimited
                'multi_user' => true,
                'custom_domain' => true,
                'ab_testing' => true,
                'features' => ['Unlimited everything', 'Multi-user access', 'White-label', 'Priority support', 'API access'],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            $model = Plan::updateOrCreate(['slug' => $plan['slug']], $plan);

            foreach ($currencyRates as $currency => $rate) {
                foreach ([PlanPrice::INTERVAL_MONTHLY, PlanPrice::INTERVAL_YEARLY] as $interval) {
                    $legacyColumn = $interval === PlanPrice::INTERVAL_MONTHLY
                        ? 'price_monthly'
                        : 'price_yearly';
                    $baseMinor = (int) $plan[$legacyColumn];
                    $amountMinor = $currency === 'GBP'
                        ? $baseMinor
                        : $this->convertMinorAmount($baseMinor, $rate, $currency, $calculator, $resolver);

                    $model->planPrices()->updateOrCreate(
                        [
                            'currency' => $currency,
                            'interval' => $interval,
                        ],
                        [
                            'amount_minor' => $amountMinor,
                            'stripe_price_id' => $this->stripePriceId($plan['slug'], $currency, $interval),
                            'is_active' => true,
                        ],
                    );
                }
            }
        }

        PlanService::clearCache();
    }

    private function convertMinorAmount(
        int $baseMinor,
        float $rate,
        string $currency,
        CurrencyPriceCalculator $calculator,
        CurrencyResolver $resolver,
    ): int {
        if ($baseMinor <= 0) {
            return 0;
        }

        $minorUnit = (int) ($resolver->metadata($currency)['minor_unit'] ?? 100);
        $amount = $calculator->convertFromBase($baseMinor / 100, $rate);

        return (int) round($amount * $minorUnit);
    }

    private function stripePriceId(string $slug, string $currency, string $interval): ?string
    {
        $configured = config("services.stripe.prices.{$slug}.{$currency}.{$interval}");

        if (filled($configured)) {
            return (string) $configured;
        }

        if ($currency === 'GBP' && $interval === PlanPrice::INTERVAL_MONTHLY) {
            return match ($slug) {
                'pro' => config('services.stripe.price_pro_monthly'),
                'agency' => config('services.stripe.price_agency_monthly'),
                default => null,
            };
        }

        return null;
    }
}
