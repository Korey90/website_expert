import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

export default function BillingSuccess({ client, business }) {
    return (
        <PortalLayout client={client} title="Payment Successful">
            <div className="flex min-h-[60vh] items-center justify-center px-4">
                <div className="max-w-md text-center">
                    <div className="mb-6 text-6xl">🎉</div>
                    <h1 className="mb-3 text-2xl font-bold text-gray-900 dark:text-white">
                        Welcome to {business.plan.charAt(0).toUpperCase() + business.plan.slice(1)} plan!
                    </h1>
                    <p className="mb-8 text-gray-500 dark:text-gray-400">
                        Your subscription has been activated. You can now enjoy all the features of your new plan.
                    </p>
                    <div className="flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <Link
                            href={route('portal.landing-pages.index')}
                            className="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700"
                        >
                            Create a Landing Page
                        </Link>
                        <Link
                            href={route('portal.billing')}
                            className="rounded-xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200"
                        >
                            View Billing
                        </Link>
                    </div>
                </div>
            </div>
        </PortalLayout>
    );
}
