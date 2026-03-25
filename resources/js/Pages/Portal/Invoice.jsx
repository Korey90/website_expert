import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusConfig = {
    sent:           { color: 'bg-blue-100 text-blue-800',    label: 'Sent — Awaiting Payment' },
    partially_paid: { color: 'bg-indigo-100 text-indigo-800', label: 'Partially Paid' },
    paid:           { color: 'bg-green-100 text-green-800',   label: 'Paid' },
    overdue:        { color: 'bg-red-100 text-red-800',       label: 'Overdue' },
    cancelled:      { color: 'bg-gray-100 text-gray-600',     label: 'Cancelled' },
};

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function Invoice({ client, invoice }) {
    const cfg = statusConfig[invoice.status] ?? { color: 'bg-gray-100 text-gray-700', label: invoice.status };

    const total      = parseFloat(invoice.total ?? 0);
    const amountPaid = parseFloat(invoice.amount_paid ?? 0);
    const amountDue  = parseFloat(invoice.amount_due ?? total);
    const paidPct    = total > 0 ? Math.min(100, Math.round((amountPaid / total) * 100)) : 0;

    const isOverdue = invoice.status !== 'paid'
        && invoice.status !== 'cancelled'
        && invoice.due_date
        && new Date(invoice.due_date) < new Date();

    return (
        <PortalLayout client={client}>
            <div className="max-w-4xl mx-auto space-y-6">

                {/* Back */}
                <Link
                    href={route('portal.invoices')}
                    className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to Invoices
                </Link>

                {/* Overdue banner */}
                {isOverdue && (
                    <div className="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3">
                        <span className="text-lg">⚠️</span>
                        <p className="text-sm font-medium text-red-700">
                            This invoice is <strong>overdue</strong> — due date was{' '}
                            <strong>{fmtDate(invoice.due_date)}</strong>.
                            Outstanding: <strong>{fmt(amountDue, invoice.currency)}</strong>.
                        </p>
                    </div>
                )}

                {/* Header card */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                        <div className="flex items-center gap-3">
                            <span className="text-xs font-semibold uppercase tracking-wider text-gray-400">Invoice</span>
                            <h1 className="text-xl font-bold text-gray-900">{invoice.number}</h1>
                        </div>
                        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold ${cfg.color}`}>
                            {cfg.label}
                        </span>
                    </div>

                    {/* Key figures */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-6 px-6 py-5">
                        <div>
                            <dt className="text-xs text-gray-400 mb-1">Total</dt>
                            <dd className="text-2xl font-bold text-gray-900">{fmt(total, invoice.currency)}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-gray-400 mb-1">Amount Due</dt>
                            <dd className={`text-2xl font-bold ${amountDue > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                {fmt(amountDue, invoice.currency)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-xs text-gray-400 mb-1">Issue Date</dt>
                            <dd className="text-sm text-gray-900">{fmtDate(invoice.issue_date)}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-gray-400 mb-1">Due Date</dt>
                            <dd className={`text-sm font-medium ${isOverdue ? 'text-red-600' : 'text-gray-900'}`}>
                                {fmtDate(invoice.due_date)}
                                {isOverdue && <span className="ml-2 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">overdue</span>}
                            </dd>
                        </div>
                    </div>

                    {/* Payment progress bar */}
                    {invoice.status !== 'cancelled' && (
                        <div className="border-t border-gray-100 px-6 py-4">
                            <div className="flex justify-between text-xs text-gray-500 mb-2">
                                <span>
                                    Paid: <strong className="text-gray-800">{fmt(amountPaid, invoice.currency)}</strong>
                                </span>
                                <span>{paidPct}% of {fmt(total, invoice.currency)}</span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div
                                    className={`h-2 rounded-full transition-all ${paidPct >= 100 ? 'bg-green-500' : paidPct > 0 ? 'bg-indigo-500' : 'bg-gray-200'}`}
                                    style={{ width: `${paidPct}%` }}
                                />
                            </div>
                        </div>
                    )}

                    {/* Stripe pay button */}
                    {invoice.stripe_payment_link && ['sent', 'overdue', 'partially_paid'].includes(invoice.status) && (
                        <div className="border-t border-gray-100 px-6 py-4">
                            <a
                                href={invoice.stripe_payment_link}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors"
                            >
                                💳 Pay Now via Stripe
                            </a>
                        </div>
                    )}
                </div>

                {/* Line Items */}
                {invoice.items && invoice.items.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                            <span className="text-sm font-semibold uppercase tracking-wider text-gray-500">Line Items</span>
                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{invoice.items.length}</span>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">
                                        <th className="px-6 py-3">#</th>
                                        <th className="px-4 py-3">Description</th>
                                        <th className="px-4 py-3 text-right">Qty</th>
                                        <th className="px-4 py-3 text-right">Unit Price</th>
                                        <th className="px-6 py-3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {invoice.items.map((item, i) => (
                                        <tr key={item.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-400">{i + 1}</td>
                                            <td className="px-4 py-4">
                                                <p className="text-sm font-medium text-gray-900">{item.description}</p>
                                                {item.details && (
                                                    <p className="mt-0.5 text-xs text-gray-500">{item.details}</p>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-right text-sm tabular-nums text-gray-700">
                                                {parseFloat(item.quantity)}
                                            </td>
                                            <td className="px-4 py-4 text-right text-sm tabular-nums text-gray-700">
                                                {fmt(item.unit_price, invoice.currency)}
                                            </td>
                                            <td className="px-6 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">
                                                {fmt(item.amount, invoice.currency)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Totals */}
                        <div className="border-t border-gray-200 bg-gray-50/60 px-6 py-5">
                            <div className="ml-auto w-full max-w-xs space-y-2">
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>Subtotal</span>
                                    <span className="tabular-nums">{fmt(invoice.subtotal, invoice.currency)}</span>
                                </div>
                                {parseFloat(invoice.discount_amount ?? 0) > 0 && (
                                    <div className="flex justify-between text-sm text-red-600">
                                        <span>Discount</span>
                                        <span className="tabular-nums">− {fmt(invoice.discount_amount, invoice.currency)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>VAT ({Math.round(parseFloat(invoice.vat_rate ?? 0))}%)</span>
                                    <span className="tabular-nums">{fmt(invoice.vat_amount, invoice.currency)}</span>
                                </div>
                                <div className="flex justify-between border-t border-gray-300 pt-3 text-base font-bold text-gray-900">
                                    <span>Total</span>
                                    <span className="tabular-nums">{fmt(total, invoice.currency)}</span>
                                </div>
                                {amountPaid > 0 && (
                                    <>
                                        <div className="flex justify-between text-sm text-green-600">
                                            <span>Paid</span>
                                            <span className="tabular-nums">− {fmt(amountPaid, invoice.currency)}</span>
                                        </div>
                                        <div className={`flex justify-between border-t border-gray-200 pt-2 text-sm font-semibold ${amountDue > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                            <span>Amount Due</span>
                                            <span className="tabular-nums">{fmt(amountDue, invoice.currency)}</span>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Notes */}
                {invoice.notes && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                            <span className="text-sm font-semibold uppercase tracking-wider text-gray-500">Notes</span>
                        </div>
                        <div className="px-6 py-5">
                            <p className="whitespace-pre-wrap text-sm leading-relaxed text-gray-700">{invoice.notes}</p>
                        </div>
                    </div>
                )}

                {/* Terms */}
                {invoice.terms && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="flex items-center gap-2 border-b border-gray-100 px-6 py-4">
                            <span className="text-sm font-semibold uppercase tracking-wider text-gray-500">Terms &amp; Conditions</span>
                        </div>
                        <div className="px-6 py-5">
                            <p className="whitespace-pre-wrap text-sm leading-relaxed text-gray-500">{invoice.terms}</p>
                        </div>
                    </div>
                )}

            </div>
        </PortalLayout>
    );
}
