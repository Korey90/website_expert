import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

const errorMessages = {
    CANCELED:      'Payment was cancelled.',
    REJECTED:      'Payment was rejected by the bank.',
    EXPIRED:       'The payment session expired. Please try again.',
    ERROR:         'A payment error occurred. Please try again.',
};

export default function PaymentResult({ client, invoice, success, errorCode }) {
    return (
        <PortalLayout client={client}>
            <div className="max-w-lg mx-auto py-8">
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-8 py-10 text-center space-y-5">

                    {success ? (
                        <>
                            <div className="text-5xl">✅</div>
                            <h1 className="text-xl font-bold text-gray-900">Payment received</h1>
                            <p className="text-sm text-gray-600">
                                Thank you! Your payment for invoice{' '}
                                <span className="font-semibold">{invoice.number}</span>{' '}
                                ({fmt(invoice.total, invoice.currency)}) has been received.
                                We will send a confirmation shortly.
                            </p>
                        </>
                    ) : (
                        <>
                            <div className="text-5xl">❌</div>
                            <h1 className="text-xl font-bold text-gray-900">Payment failed</h1>
                            <p className="text-sm text-gray-600">
                                {errorCode && errorMessages[errorCode]
                                    ? errorMessages[errorCode]
                                    : 'Your payment could not be processed. Please try again or contact us.'}
                            </p>
                        </>
                    )}

                    <div className="flex flex-col sm:flex-row gap-3 justify-center pt-2">
                        <Link
                            href={route('portal.invoices.show', invoice.id)}
                            className="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            View Invoice
                        </Link>
                        {!success && (
                            <Link
                                href={route('portal.invoices.pay', invoice.id)}
                                className="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Try Again
                            </Link>
                        )}
                        <Link
                            href={route('portal.dashboard')}
                            className="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            Go to Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </PortalLayout>
    );
}
