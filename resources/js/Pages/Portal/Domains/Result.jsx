import { Head, Link } from '@inertiajs/react';
import useCurrency from '@/Hooks/useCurrency';
import PortalLayout from '@/Layouts/PortalLayout';

export default function DomainsResult({ client, order, payment }) {
    const { formatCurrency } = useCurrency();
    const isSuccess   = payment === 'success';
    const isCancelled = payment === 'cancelled';

    return (
        <PortalLayout client={client}>
            <Head title={isSuccess ? 'Payment Received' : 'Payment Cancelled'} />

            <div className="max-w-lg mx-auto py-10 text-center space-y-6">
                {isSuccess ? (
                    <>
                        <div className="text-6xl">🎉</div>
                        <h1 className="text-2xl font-bold text-gray-900">Payment Received!</h1>
                        <p className="text-gray-500">
                            Your order for <strong className="font-mono">{order.full_domain}</strong> has been received.
                            Our team will process the registration shortly and you'll receive a confirmation email.
                        </p>

                        <div className="rounded-xl border border-green-200 bg-green-50 px-6 py-4 text-left space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Domain</span>
                                <span className="font-mono font-semibold">{order.full_domain}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Action</span>
                                <span className="capitalize">{order.action}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Period</span>
                                <span>{order.years} {order.years === 1 ? 'year' : 'years'}</span>
                            </div>
                            <div className="flex justify-between text-sm font-semibold border-t border-green-200 pt-2">
                                <span>Amount Paid</span>
                                <span>{formatCurrency(order.retail_price, order.currency)}</span>
                            </div>
                        </div>

                        <p className="text-sm text-gray-400">
                            Order reference: <span className="font-mono text-xs">{order.id}</span>
                        </p>
                    </>
                ) : isCancelled ? (
                    <>
                        <div className="text-6xl">❌</div>
                        <h1 className="text-2xl font-bold text-gray-900">Payment Cancelled</h1>
                        <p className="text-gray-500">
                            Your payment was cancelled. No charge was made. Your order for{' '}
                            <strong className="font-mono">{order.full_domain}</strong> is still pending.
                        </p>

                        <Link
                            href={route('portal.domains.checkout', order.id)}
                            className="inline-block rounded-xl bg-red-600 px-6 py-3 font-bold text-white hover:bg-red-700 transition-colors"
                        >
                            Try Again →
                        </Link>
                    </>
                ) : (
                    <>
                        <div className="text-6xl">⏳</div>
                        <h1 className="text-2xl font-bold text-gray-900">Processing Payment</h1>
                        <p className="text-gray-500">
                            Your payment is being processed. Please wait a moment.
                        </p>
                    </>
                )}

                <div className="pt-4 flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        href={route('portal.dashboard')}
                        className="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        ← Back to Dashboard
                    </Link>
                    <Link
                        href={route('domains.index')}
                        className="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Register Another Domain
                    </Link>
                </div>
            </div>
        </PortalLayout>
    );
}
