import EmptyState from '@/Components/Shared/EmptyState';
import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusColors = {
    draft:    'bg-gray-100 text-gray-700',
    sent:     'bg-blue-100 text-blue-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    expired:  'bg-orange-100 text-orange-800',
};

function StatusBadge({ status }) {
    const cls = statusColors[status] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${cls}`}>
            {status}
        </span>
    );
}

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

export default function Quotes({ client, quotes }) {
    return (
        <PortalLayout client={client}>
            <div className="max-w-5xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Quotes</h1>

                {quotes.length === 0 ? (
                    <EmptyState
                        icon="📋"
                        title="No quotes yet"
                        description="Quotes sent to you will appear here."
                    />
                ) : (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quote #</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Until</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accepted On</th>
                                    <th className="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {quotes.map(q => (
                                    <tr key={q.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 text-sm font-medium text-gray-900">{q.number}</td>
                                        <td className="px-5 py-3"><StatusBadge status={q.status} /></td>
                                        <td className="px-5 py-3 text-sm text-gray-900">{fmt(q.total, q.currency)}</td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{fmtDate(q.valid_until)}</td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{fmtDate(q.accepted_at)}</td>
                                        <td className="px-5 py-3 text-right">
                                            {q.status !== 'draft' && (
                                                <Link
                                                    href={route('portal.quotes.show', q.id)}
                                                    className="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800"
                                                >
                                                    View →
                                                </Link>
                                            )}
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

