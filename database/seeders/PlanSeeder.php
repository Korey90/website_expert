<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'              => 'free',
                'name'              => 'Free',
                'description'       => 'Get started — no credit card required.',
                'price_monthly'     => 0,
                'price_yearly'      => 0,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'max_landing_pages' => 1,
                'max_ai_per_month'  => 3,
                'multi_user'        => false,
                'custom_domain'     => false,
                'ab_testing'        => false,
                'features'          => ['1 Landing Page', '3 AI generations/month', 'Basic analytics'],
                'is_active'         => true,
                'sort_order'        => 1,
            ],
            [
                'slug'              => 'basic',
                'name'              => 'Basic',
                'description'       => 'For freelancers getting started.',
                'price_monthly'     => 2900,   // £29.00
                'price_yearly'      => 27000,  // £270.00 (~£22.50/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'max_landing_pages' => 5,
                'max_ai_per_month'  => 10,
                'multi_user'        => false,
                'custom_domain'     => false,
                'ab_testing'        => false,
                'features'          => ['5 Landing Pages', '10 AI generations/month', 'Lead management', 'Email notifications'],
                'is_active'         => true,
                'sort_order'        => 2,
            ],
            [
                'slug'              => 'pro',
                'name'              => 'Pro',
                'description'       => 'For professionals who want to scale.',
                'price_monthly'     => 4900,   // £49.00
                'price_yearly'      => 46800,  // £468.00 (~£39/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'max_landing_pages' => null,   // unlimited
                'max_ai_per_month'  => 50,
                'multi_user'        => false,
                'custom_domain'     => true,
                'ab_testing'        => true,
                'features'          => ['Unlimited Landing Pages', '50 AI generations/month', 'A/B Testing', 'Custom Domain', 'Advanced analytics'],
                'is_active'         => true,
                'sort_order'        => 3,
            ],
            [
                'slug'              => 'agency',
                'name'              => 'Agency',
                'description'       => 'For agencies managing multiple clients.',
                'price_monthly'     => 14900,  // £149.00
                'price_yearly'      => 143000, // £1430.00 (~£119/mo)
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'max_landing_pages' => null,   // unlimited
                'max_ai_per_month'  => null,   // unlimited
                'multi_user'        => true,
                'custom_domain'     => true,
                'ab_testing'        => true,
                'features'          => ['Unlimited everything', 'Multi-user access', 'White-label', 'Priority support', 'API access'],
                'is_active'         => true,
                'sort_order'        => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
