import { Head, Link, router } from '@inertiajs/react';
import useCurrency from '@/Hooks/useCurrency';
import PortalLayout from '@/Layouts/PortalLayout';
import { useState } from 'react';

export default function DomainsCheckout({ client, order }) {
    const { formatCurrency } = useCurrency();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    function handlePay(e) {
        e.preventDefault();
        setSubmitting(true);
        setError(null);
        router.post(
            route('portal.domains.pay', order.id),
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
        <PortalLayout client={client}>
            <Head title={`Payment — ${order.full_domain}`} />

            <div className="max-w-xl mx-auto space-y-6">
                <div>
                    <Link
                        href={route('portal.domains.order') + `?domain=${encodeURIComponent(order.full_domain.replace(/\.[^.]+$/, ''))}&tld=${encodeURIComponent('.' + order.full_domain.split('.').slice(1).join('.'))}`}
                        className="text-sm text-gray-500 hover:text-gray-700 inline-block mb-1"
                    >
                        ← Back to order
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900">Complete Payment</h1>
                </div>

                {/* Order Summary */}
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-4">Order Summary</h2>
                    <div className="space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Domain</span>
                            <span className="font-mono font-semibold text-gray-900">{order.full_domain}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Action</span>
                            <span className="capitalize text-gray-900">{order.action}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Period</span>
                            <span className="text-gray-900">{order.years} {order.years === 1 ? 'year' : 'years'}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Subtotal (ex. VAT)</span>
                            <span className="text-gray-900">{formatCurrency(order.retail_price, order.currency)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-600">VAT ({order.vat_rate}%)</span>
                            <span className="text-gray-900">{formatCurrency(order.vat_amount, order.currency)}</span>
                        </div>
                        <div className="border-t border-gray-100 pt-3 flex justify-between">
                            <span className="font-semibold text-gray-900">Total (inc. VAT)</span>
                            <span className="text-xl font-black text-gray-900">
                                {formatCurrency(order.total, order.currency)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Payment */}
                <div className="bg-white rounded-xl border border-gray-200 p-6">
                    <h2 className="font-semibold text-gray-900 mb-2">Payment</h2>
                    <p className="text-sm text-gray-500 mb-4">
                        You will be redirected to our secure payment processor (Stripe) to complete payment.
                    </p>

                    {error && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    {order.status !== 'pending_payment' ? (
                        <div className="rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-800">
                            This order has status <strong>{order.status}</strong> and cannot be paid again.
                        </div>
                    ) : (
                        <form onSubmit={handlePay}>
                            <button
                                type="submit"
                                disabled={submitting}
                                className="w-full rounded-xl bg-red-600 py-3 font-bold text-white hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                {submitting
                                    ? <><span className="animate-spin">⏳</span> Redirecting to Stripe…</>
                                    : <>🔒 Pay {formatCurrency(order.total, order.currency)} Securely</>
                                }
                            </button>
                        </form>
                    )}

                    <p className="mt-3 text-xs text-center text-gray-400">
                        Secured by Stripe · SSL encrypted · No card data stored on our servers
                    </p>
                </div>
            </div>
        </PortalLayout>
    );
}
