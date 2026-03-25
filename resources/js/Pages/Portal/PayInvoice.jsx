import PortalLayout from '@/Layouts/PortalLayout';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

export default function PayInvoice({ client, invoice, stripeEnabled, payuEnabled }) {
    const [loading, setLoading] = useState(null);

    const amountDue = parseFloat(invoice.amount_due ?? invoice.total ?? 0);

    function payWithStripe() {
        setLoading('stripe');
        router.post(route('portal.invoices.pay.stripe', invoice.id), {}, {
            onError: () => setLoading(null),
        });
    }

    function payWithPayu() {
        setLoading('payu');
        router.post(route('portal.invoices.pay.payu', invoice.id), {}, {
            onError: () => setLoading(null),
        });
    }

    const hasMethods = stripeEnabled || payuEnabled;

    return (
        <PortalLayout client={client}>
            <div className="max-w-2xl mx-auto space-y-6">

                {/* Back */}
                <Link
                    href={route('portal.invoice', invoice.id)}
                    className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to Invoice
                </Link>

                {/* Header */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-5">
                    <h1 className="text-xl font-bold text-gray-900">Pay Invoice {invoice.number}</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Amount due:{' '}
                        <span className="font-semibold text-gray-800">
                            {fmt(amountDue, invoice.currency)}
                        </span>
                    </p>
                </div>

                {/* Payment methods */}
                {hasMethods ? (
                    <div className="space-y-4">
                        <p className="text-sm font-medium text-gray-600 uppercase tracking-wide">
                            Select payment method
                        </p>

                        {stripeEnabled && (
                            <button
                                onClick={payWithStripe}
                                disabled={loading !== null}
                                className="w-full flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm hover:border-indigo-400 hover:shadow-md transition-all disabled:opacity-60 text-left"
                            >
                                <span className="text-3xl">💳</span>
                                <div className="flex-1">
                                    <p className="font-semibold text-gray-900">Card payment via Stripe</p>
                                    <p className="text-sm text-gray-500 mt-0.5">
                                        Visa, Mastercard, Apple Pay, Google Pay
                                    </p>
                                </div>
                                {loading === 'stripe' ? (
                                    <svg className="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                ) : (
                                    <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                )}
                            </button>
                        )}

                        {payuEnabled && (
                            <button
                                onClick={payWithPayu}
                                disabled={loading !== null}
                                className="w-full flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm hover:border-yellow-400 hover:shadow-md transition-all disabled:opacity-60 text-left"
                            >
                                <span className="text-3xl">🏦</span>
                                <div className="flex-1">
                                    <p className="font-semibold text-gray-900">PayU</p>
                                    <p className="text-sm text-gray-500 mt-0.5">
                                        BLIK, bank transfer, installments
                                    </p>
                                </div>
                                {loading === 'payu' ? (
                                    <svg className="animate-spin h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                ) : (
                                    <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                )}
                            </button>
                        )}
                    </div>
                ) : (
                    <div className="rounded-xl border border-yellow-200 bg-yellow-50 px-6 py-5 text-sm text-yellow-800">
                        Online payments are not currently enabled. Please contact us to arrange payment.
                    </div>
                )}

                {/* Security note */}
                <p className="text-xs text-center text-gray-400">
                    🔒 Payments are processed securely. Your card details are never stored on our servers.
                </p>
            </div>
        </PortalLayout>
    );
}
