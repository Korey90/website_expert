import PortalLayout from '@/Layouts/PortalLayout';
import { Link, router } from '@inertiajs/react';
import usePortalTrans from '@/Hooks/usePortalTrans';

const PLAN_COLORS = {
    free:   'bg-gray-100 text-gray-700 border-gray-200',
    pro:    'bg-blue-50 text-blue-700 border-blue-200',
    agency: 'bg-purple-50 text-purple-700 border-purple-200',
};

const PLAN_CTA_COLORS = {
    free:   'bg-brand-600 hover:bg-brand-700 text-white',
    pro:    'bg-blue-600 hover:bg-blue-700 text-white',
    agency: 'bg-purple-700 hover:bg-purple-800 text-white',
};

function UsageBar({ used, limit, label }) {
    const isUnlimited = limit === null || limit === undefined;
    const pct = isUnlimited ? 0 : Math.min(100, Math.round((used / limit) * 100));
    const color = pct >= 90 ? 'bg-red-500' : pct >= 70 ? 'bg-amber-400' : 'bg-brand-500';

    return (
        <div>
            <div className="mb-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                <span>{label}</span>
                <span className="font-medium">
                    {used} / {isUnlimited ? '∞' : limit}
                </span>
            </div>
            {!isUnlimited && (
                <div className="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                    <div className={`h-2 rounded-full transition-all ${color}`} style={{ width: `${pct}%` }} />
                </div>
            )}
        </div>
    );
}

function PlanCard({ plan, current, effectivePlan, onUpgrade }) {
    const isCurrent = plan.key === effectivePlan;
    const isFree    = plan.key === 'free';

    return (
        <div className={`relative rounded-2xl border p-6 transition ${isCurrent ? 'border-brand-500 shadow-md ring-2 ring-brand-500/30' : 'border-gray-200 dark:border-gray-700'}`}>
            {isCurrent && (
                <span className="absolute -top-3 left-4 rounded-full bg-brand-600 px-3 py-0.5 text-xs font-semibold text-white">
                    Current plan
                </span>
            )}

            <h3 className="text-lg font-bold text-gray-900 dark:text-white">{plan.name}</h3>
            <p className="mt-1 text-2xl font-extrabold text-gray-900 dark:text-white">
                {plan.price === 0 ? 'Free' : `£${plan.price}/mo`}
            </p>

            <ul className="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li className="flex items-center gap-2">
                    <span className="text-green-500">✓</span>
                    {plan.landing_pages === null ? 'Unlimited landing pages' : `${plan.landing_pages} landing pages`}
                </li>
                <li className="flex items-center gap-2">
                    <span className="text-green-500">✓</span>
                    {plan.ai_per_month === null ? 'Unlimited AI generations' : `${plan.ai_per_month} AI generations/month`}
                </li>
            </ul>

            {!isCurrent && !isFree && (
                <button
                    onClick={() => onUpgrade(plan.key)}
                    className={`mt-5 w-full rounded-xl py-2.5 text-sm font-semibold transition ${PLAN_CTA_COLORS[plan.key]}`}
                >
                    Upgrade to {plan.name}
                </button>
            )}
            {isCurrent && !isFree && (
                <Link
                    href={route('portal.billing.portal')}
                    method="post"
                    as="button"
                    className="mt-5 block w-full rounded-xl border border-gray-300 py-2.5 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Manage subscription
                </Link>
            )}
        </div>
    );
}

export default function BillingIndex({
    client,
    business,
    plans,
    ai_used,
    ai_limit,
    ai_remaining,
    lp_count,
    lp_limit,
    can_create_lp,
    can_use_ai,
}) {
    const t = usePortalTrans();

    const handleUpgrade = (plan) => {
        router.post(route('portal.billing.checkout', { plan }));
    };

    const planBadgeClass = PLAN_COLORS[business.effective_plan] ?? PLAN_COLORS.free;
    const isUnlimitedLp = lp_limit === null || lp_limit >= 999999;
    const isUnlimitedAi = ai_limit === null || ai_limit >= 999999;

    return (
        <PortalLayout client={client} title="Billing & Plan">
            <div className="mx-auto max-w-4xl space-y-8 px-4 py-8">

                {/* Header */}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Billing & Plan</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Manage your SaaS subscription and usage.
                        </p>
                    </div>
                    <span className={`inline-flex items-center rounded-full border px-4 py-1 text-sm font-semibold capitalize ${planBadgeClass}`}>
                        {business.effective_plan} plan
                        {business.on_trial && (
                            <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">
                                Trial — {business.trial_remaining}d left
                            </span>
                        )}
                    </span>
                </div>

                {/* Usage */}
                <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-white">Usage this month</h2>
                    <div className="space-y-4">
                        <UsageBar
                            used={lp_count}
                            limit={isUnlimitedLp ? null : lp_limit}
                            label="Landing Pages"
                        />
                        <UsageBar
                            used={ai_used}
                            limit={isUnlimitedAi ? null : ai_limit}
                            label="AI Generations"
                        />
                    </div>

                    {(!can_create_lp || !can_use_ai) && (
                        <div className="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            {!can_create_lp && (
                                <p>⚠️ You've reached your landing page limit. Upgrade to create more.</p>
                            )}
                            {!can_use_ai && (
                                <p>⚠️ You've used all AI generations for this month. Upgrade for more.</p>
                            )}
                        </div>
                    )}
                </div>

                {/* Plans */}
                <div>
                    <h2 className="mb-4 text-base font-semibold text-gray-900 dark:text-white">Available plans</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        {plans.map((plan) => (
                            <PlanCard
                                key={plan.key}
                                plan={plan}
                                current={business.plan}
                                effectivePlan={business.effective_plan}
                                onUpgrade={handleUpgrade}
                            />
                        ))}
                    </div>
                </div>

                {/* Info */}
                <p className="text-center text-xs text-gray-400">
                    Payments processed securely by Stripe. Cancel anytime.
                </p>
            </div>
        </PortalLayout>
    );
}
