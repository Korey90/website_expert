import { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';

/**
 * Onboarding/Complete — step 2 of 2.
 * Shows success message and auto-redirects to /admin after 4 seconds.
 */
export default function OnboardingComplete({ business }) {
    useEffect(() => {
        const timer = setTimeout(() => {
            window.location.href = '/admin';
        }, 4000);
        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col">
            <Head title="You're all set!" />

            {/* Top bar */}
            <header className="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-4">
                <div className="mx-auto max-w-2xl flex items-center justify-between">
                    <Link href="/">
                        <ApplicationLogo className="h-8 w-auto fill-current text-gray-900 dark:text-white" />
                    </Link>
                    <span className="text-sm text-gray-500 dark:text-gray-400 font-medium">
                        Step 2 of 2
                    </span>
                </div>

                {/* Full progress */}
                <div className="mx-auto max-w-2xl mt-3">
                    <div className="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div className="h-full rounded-full bg-green-500 transition-all duration-700" style={{ width: '100%' }} />
                    </div>
                </div>
            </header>

            {/* Main */}
            <main className="flex-1 flex items-center justify-center px-4 py-16">
                <div className="mx-auto max-w-md text-center">
                    {/* Success icon */}
                    <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <svg className="h-10 w-10 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <h1 className="text-3xl font-display font-bold text-gray-900 dark:text-white">
                        You're all set{business?.name ? `, ${business.name}` : ''}!
                    </h1>
                    <p className="mt-3 text-base text-gray-500 dark:text-gray-400">
                        Your brand profile has been saved. You're ready to create your first AI-powered landing page.
                    </p>

                    {/* Auto-redirect indicator */}
                    <p className="mt-6 text-sm text-gray-400 dark:text-gray-600">
                        Redirecting to dashboard in a moment…
                    </p>

                    {/* Manual CTA in case redirect is slow */}
                    <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a
                            href="/admin"
                            className="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 transition-colors"
                        >
                            Go to Dashboard
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <Link
                            href={route('business.profile.edit')}
                            className="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline-offset-2 hover:underline"
                        >
                            Edit profile
                        </Link>
                    </div>
                </div>
            </main>
        </div>
    );
}
