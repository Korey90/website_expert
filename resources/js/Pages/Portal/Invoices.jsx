import EmptyState from '@/Components/Shared/EmptyState';
import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusColors = {
    draft:   'bg-gray-100 text-gray-700',
    sent:    'bg-blue-100 text-blue-800',
    paid:    'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    void:    'bg-gray-200 text-gray-500',
};

function StatusBadge({ status }) {
    const cls = statusColors[status] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${cls}`}>
            {status}
        </span>
    );
}

export default function Invoices({ client, invoices }) {
    const totalDue = invoices
        .filter(i => ['sent', 'overdue'].includes(i.status))
        .reduce((sum, i) => sum + parseFloat(i.amount_due), 0);

    return (
        <PortalLayout client={client}>
            <div className="max-w-5xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Invoices</h1>
                    {totalDue > 0 && (
                        <div className="bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-2 rounded-lg">
                            Outstanding: £{totalDue.toFixed(2)}
                        </div>
                    )}
                </div>

                {invoices.length === 0 ? (
                    <EmptyState
                        icon="🧾"
                        title="No invoices yet"
                        description="Your invoices will appear here when issued."
                    />
                ) : (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount Due</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {invoices.map(inv => (
                                    <tr key={inv.id} className={`hover:bg-gray-50 ${inv.status === 'overdue' ? 'bg-red-50' : ''}`}>
                                        <td className="px-5 py-3 text-sm font-medium text-gray-900">{inv.number}</td>
                                        <td className="px-5 py-3"><StatusBadge status={inv.status} /></td>
                                        <td className="px-5 py-3 text-sm text-gray-900">
                                            {inv.currency ?? '£'}{parseFloat(inv.total).toFixed(2)}
                                        </td>
                                        <td className="px-5 py-3 text-sm font-semibold text-gray-900">
                                            {parseFloat(inv.amount_due) > 0
                                                ? `${inv.currency ?? '£'}${parseFloat(inv.amount_due).toFixed(2)}`
                                                : <span className="text-green-600">Paid</span>}
                                        </td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{inv.due_date ?? '—'}</td>
                                        <td className="px-5 py-3">
                                            <div className="flex items-center gap-2">
                                                <Link
                                                    href={route('portal.invoices.show', inv.id)}
                                                    className="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors"
                                                >
                                                    View →
                                                </Link>
                                                {inv.stripe_payment_link && ['sent', 'overdue'].includes(inv.status) ? (
                                                    <a
                                                        href={inv.stripe_payment_link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors"
                                                    >
                                                        Pay Now
                                                    </a>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
