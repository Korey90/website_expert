import { Head, Link, router, usePage } from '@inertiajs/react';
import useCurrency from '@/Hooks/useCurrency';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { useState } from 'react';

export default function DomainsCheckout({ order, auth }) {
    const { footer } = usePage().props;
    const { formatCurrency } = useCurrency();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError]           = useState(null);

    function handlePay(e) {
        e.preventDefault();
        setSubmitting(true);
        setError(null);
        router.post(
            route('domains.pay', order.id),
            {},
            {
                onError: (errs) => {
                    setError(errs.stripe ?? errs.order ?? 'An error occurred. Please try again.');
                    setSubmitting(false);
                },
            }
        );
    }

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head title={`Payment — ${order.full_domain}`} />

            {/* Page header */}
            <section className="relative overflow-hidden border-b border-neutral-100 dark:border-neutral-800 pt-24 pb-10">
                <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50/30 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
                <div className="relative mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('domains.order') + `?domain=${encodeURIComponent(order.full_domain.replace(/\.[^.]+$/, ''))}&tld=${encodeURIComponent('.' + order.full_domain.split('.').slice(1).join('.'))}`}
                        className="inline-flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-white transition-colors mb-4"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to order
                    </Link>
                    <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">Complete Payment</h1>
                </div>
            </section>

            <div className="mx-auto max-w-xl px-4 sm:px-6 lg:px-8 py-10 space-y-6">
                {/* Order summary */}
                <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6">
                    <h2 className="font-semibold text-neutral-900 dark:text-white mb-4">Order Summary</h2>
                    <div className="space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="text-neutral-500 dark:text-neutral-400">Domain</span>
                            <span className="font-mono font-semibold text-neutral-900 dark:text-white">{order.full_domain}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-neutral-500 dark:text-neutral-400">Action</span>
                            <span className="capitalize text-neutral-900 dark:text-white">{order.action}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-neutral-500 dark:text-neutral-400">Period</span>
                            <span className="text-neutral-900 dark:text-white">{order.years} {order.years === 1 ? 'year' : 'years'}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-neutral-500 dark:text-neutral-400">Subtotal (ex. VAT)</span>
                            <span className="text-neutral-900 dark:text-white">{formatCurrency(order.retail_price, order.currency)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-neutral-500 dark:text-neutral-400">VAT ({order.vat_rate}%)</span>
                            <span className="text-neutral-900 dark:text-white">{formatCurrency(order.vat_amount, order.currency)}</span>
                        </div>
                        <div className="border-t border-neutral-100 dark:border-neutral-800 pt-3 flex justify-between">
                            <span className="font-semibold text-neutral-900 dark:text-white">Total (inc. VAT)</span>
                            <span className="text-2xl font-black text-neutral-900 dark:text-white">
                                {formatCurrency(order.total, order.currency)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Payment */}
                <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6">
                    <h2 className="font-semibold text-neutral-900 dark:text-white mb-1">Payment</h2>
                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                        You will be redirected to our secure payment processor (Stripe) to complete payment.
                    </p>

                    {error && (
                        <div className="mb-4 rounded-xl border border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10 px-4 py-3 text-sm text-red-700 dark:text-red-400">
                            {error}
                        </div>
                    )}

                    {order.status !== 'pending_payment' ? (
                        <div className="rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-900/40 px-4 py-3 text-sm text-amber-800 dark:text-amber-400">
                            This order has status <strong>{order.status}</strong> and cannot be paid again.
                        </div>
                    ) : (
                        <form onSubmit={handlePay}>
                            <button
                                type="submit"
                                disabled={submitting}
                                className="w-full rounded-xl bg-brand-500 py-3 font-bold text-white hover:bg-brand-600 active:scale-[0.99] transition-all shadow-lg shadow-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                {submitting ? (
                                    <><svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" /></svg> Redirecting to Stripe…</>
                                ) : (
                                    <><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg> Pay {formatCurrency(order.total, order.currency)} Securely</>
                                )}
                            </button>
                        </form>
                    )}

                    <p className="mt-4 text-xs text-center text-neutral-400 dark:text-neutral-600">
                        Secured by Stripe · SSL encrypted · No card data stored on our servers
                    </p>
                </div>
            </div>
        </MarketingLayout>
    );
}
