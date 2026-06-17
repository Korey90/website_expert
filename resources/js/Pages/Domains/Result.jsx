import { Head, Link, usePage } from '@inertiajs/react';
import useCurrency from '@/Hooks/useCurrency';
import MarketingLayout from '@/Layouts/MarketingLayout';

export default function DomainsResult({ order, payment, auth }) {
    const { footer } = usePage().props;
    const { formatCurrency } = useCurrency();
    const success  = payment === 'success' || order.status === 'completed';
    const cancelled = payment === 'cancelled';

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head title={success ? `Domain Order Confirmed — ${order.full_domain}` : `Payment ${cancelled ? 'Cancelled' : 'Pending'} — ${order.full_domain}`} />

            {/* Header */}
            <section className="relative overflow-hidden border-b border-neutral-100 dark:border-neutral-800 pt-24 pb-10">
                <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50/30 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
                <div className="relative mx-auto max-w-xl px-4 sm:px-6 lg:px-8 text-center">
                    {success ? (
                        <>
                            <div className="mx-auto mb-4 w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg className="w-7 h-7 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">Payment Received</h1>
                            <p className="text-neutral-500 dark:text-neutral-400 mt-1">Your domain order has been placed and will be registered shortly.</p>
                        </>
                    ) : cancelled ? (
                        <>
                            <div className="mx-auto mb-4 w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <svg className="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">Payment Cancelled</h1>
                            <p className="text-neutral-500 dark:text-neutral-400 mt-1">Your payment was cancelled. Your order is still saved — you can try again.</p>
                        </>
                    ) : (
                        <>
                            <div className="mx-auto mb-4 w-14 h-14 rounded-full bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center">
                                <svg className="w-7 h-7 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">Order Pending</h1>
                            <p className="text-neutral-500 dark:text-neutral-400 mt-1">Your order is awaiting payment.</p>
                        </>
                    )}
                </div>
            </section>

            <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8 py-10 space-y-6">
                {/* Order details */}
                <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6">
                    <h2 className="font-semibold text-neutral-900 dark:text-white mb-4">Order Details</h2>
                    <div className="space-y-3 text-sm">
                        <div className="flex justify-between">
                            <span className="text-neutral-500 dark:text-neutral-400">Domain</span>
                            <span className="font-mono font-semibold text-neutral-900 dark:text-white">{order.full_domain}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-neutral-500 dark:text-neutral-400">Action</span>
                            <span className="capitalize text-neutral-900 dark:text-white">{order.action}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-neutral-500 dark:text-neutral-400">Period</span>
                            <span className="text-neutral-900 dark:text-white">{order.years} {order.years === 1 ? 'year' : 'years'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-neutral-500 dark:text-neutral-400">Status</span>
                            <span className="capitalize text-neutral-900 dark:text-white font-medium">{order.status.replace(/_/g, ' ')}</span>
                        </div>
                        <div className="border-t border-neutral-100 dark:border-neutral-800 pt-3 flex justify-between">
                            <span className="font-semibold text-neutral-900 dark:text-white">Total Paid</span>
                            <span className="text-xl font-black text-neutral-900 dark:text-white">
                                {formatCurrency(order.retail_price, order.currency)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* What next */}
                {success && (
                    <div className="bg-green-50 dark:bg-green-900/10 rounded-2xl border border-green-200 dark:border-green-900/30 p-6 space-y-2">
                        <h3 className="font-semibold text-green-900 dark:text-green-400">What happens next?</h3>
                        <ul className="text-sm text-green-800 dark:text-green-300 space-y-1.5 list-disc list-inside">
                            <li>You'll receive a confirmation email shortly.</li>
                            <li>We'll register your domain — usually within minutes.</li>
                            <li>Once registered, you'll receive another email with your domain details.</li>
                        </ul>
                    </div>
                )}

                {/* CTA buttons */}
                <div className="flex flex-col sm:flex-row gap-3">
                    {cancelled && (
                        <Link
                            href={route('domains.checkout', order.id)}
                            className="flex-1 text-center rounded-xl bg-brand-500 px-5 py-3 font-semibold text-white hover:bg-brand-600 transition-colors"
                        >
                            Try Payment Again
                        </Link>
                    )}
                    <Link
                        href={route('portal.domains.index')}
                        className="flex-1 text-center rounded-xl border border-neutral-200 dark:border-neutral-700 px-5 py-3 font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                    >
                        View My Domains
                    </Link>
                    <Link
                        href={route('domains.index')}
                        className="flex-1 text-center rounded-xl border border-neutral-200 dark:border-neutral-700 px-5 py-3 font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                    >
                        Register Another
                    </Link>
                </div>
            </div>
        </MarketingLayout>
    );
}
