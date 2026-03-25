import PortalLayout from '@/Layouts/PortalLayout';
import { Link, router, usePage } from '@inertiajs/react';

const statusConfig = {
    draft:    { color: 'bg-gray-100 text-gray-700',    label: 'Draft' },
    sent:     { color: 'bg-blue-100 text-blue-800',    label: 'Sent — Awaiting Your Response' },
    accepted: { color: 'bg-green-100 text-green-800',  label: 'Accepted' },
    rejected: { color: 'bg-red-100 text-red-800',      label: 'Rejected' },
    expired:  { color: 'bg-orange-100 text-orange-800', label: 'Expired' },
};

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function Quote({ client, quote }) {
    const { props } = usePage();
    const flash = props.flash ?? {};
    const cfg = statusConfig[quote.status] ?? statusConfig.draft;
    const isSent = quote.status === 'sent';

    function accept() {
        if (!confirm('Are you sure you want to accept this quote?')) return;
        router.post(route('portal.quotes.accept', quote.id));
    }

    function reject() {
        if (!confirm('Are you sure you want to reject this quote?')) return;
        router.post(route('portal.quotes.reject', quote.id));
    }

    return (
        <PortalLayout client={client}>
            <div className="max-w-4xl mx-auto space-y-6">

                {/* Flash messages */}
                {flash.success && (
                    <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <Link href={route('portal.quotes')} className="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                            ← Back to Quotes
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">{quote.number}</h1>
                        <span className={`mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cfg.color}`}>
                            {cfg.label}
                        </span>
                    </div>

                    {isSent && (
                        <div className="flex gap-3">
                            <button
                                onClick={reject}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-red-300 text-red-700 bg-white hover:bg-red-50 transition-colors"
                            >
                                ✕ Reject
                            </button>
                            <button
                                onClick={accept}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"
                            >
                                ✓ Accept Quote
                            </button>
                        </div>
                    )}
                </div>

                {/* Summary card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-6">
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Total Value</p>
                            <p className="mt-1 text-2xl font-bold text-gray-900">{fmt(quote.total, quote.currency)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Valid Until</p>
                            <p className="mt-1 text-base font-medium text-gray-900">{fmtDate(quote.valid_until)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Issued On</p>
                            <p className="mt-1 text-base font-medium text-gray-900">{fmtDate(quote.sent_at ?? quote.created_at)}</p>
                        </div>
                        {quote.accepted_at && (
                            <div>
                                <p className="text-xs text-gray-500 uppercase tracking-wide">Accepted On</p>
                                <p className="mt-1 text-base font-medium text-green-700">{fmtDate(quote.accepted_at)}</p>
                            </div>
                        )}
                        {quote.rejected_at && (
                            <div>
                                <p className="text-xs text-gray-500 uppercase tracking-wide">Rejected On</p>
                                <p className="mt-1 text-base font-medium text-red-700">{fmtDate(quote.rejected_at)}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Line items */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100">
                        <h2 className="font-semibold text-gray-900">Line Items</h2>
                    </div>
                    <table className="min-w-full divide-y divide-gray-100">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {quote.items.map(item => (
                                <tr key={item.id}>
                                    <td className="px-6 py-4">
                                        <p className="text-sm font-medium text-gray-900">{item.description}</p>
                                        {item.details && <p className="text-xs text-gray-500 mt-0.5">{item.details}</p>}
                                    </td>
                                    <td className="px-6 py-4 text-right text-sm text-gray-700">{item.quantity}</td>
                                    <td className="px-6 py-4 text-right text-sm text-gray-700">{fmt(item.unit_price, quote.currency)}</td>
                                    <td className="px-6 py-4 text-right text-sm font-medium text-gray-900">{fmt(item.amount, quote.currency)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="bg-gray-50">
                            <tr>
                                <td colSpan={3} className="px-6 py-3 text-right text-sm text-gray-500">Subtotal</td>
                                <td className="px-6 py-3 text-right text-sm text-gray-700">{fmt(quote.subtotal, quote.currency)}</td>
                            </tr>
                            {parseFloat(quote.discount_amount) > 0 && (
                                <tr>
                                    <td colSpan={3} className="px-6 py-3 text-right text-sm text-gray-500">Discount</td>
                                    <td className="px-6 py-3 text-right text-sm text-red-600">−{fmt(quote.discount_amount, quote.currency)}</td>
                                </tr>
                            )}
                            <tr>
                                <td colSpan={3} className="px-6 py-3 text-right text-sm text-gray-500">VAT ({quote.vat_rate}%)</td>
                                <td className="px-6 py-3 text-right text-sm text-gray-700">{fmt(quote.vat_amount, quote.currency)}</td>
                            </tr>
                            <tr className="border-t border-gray-200">
                                <td colSpan={3} className="px-6 py-4 text-right text-sm font-bold text-gray-900">Total</td>
                                <td className="px-6 py-4 text-right text-sm font-bold text-gray-900">{fmt(quote.total, quote.currency)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {/* Notes */}
                {quote.notes && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-2">Notes</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{quote.notes}</p>
                    </div>
                )}

                {/* Terms */}
                {quote.terms && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-2">Terms &amp; Conditions</h2>
                        <p className="text-sm text-gray-600 whitespace-pre-line">{quote.terms}</p>
                    </div>
                )}

                {/* Bottom actions (repeated for convenience) */}
                {isSent && (
                    <div className="flex justify-end gap-3 pb-4">
                        <button
                            onClick={reject}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-red-300 text-red-700 bg-white hover:bg-red-50 transition-colors"
                        >
                            ✕ Reject
                        </button>
                        <button
                            onClick={accept}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors"
                        >
                            ✓ Accept Quote
                        </button>
                    </div>
                )}

            </div>
        </PortalLayout>
    );
}
