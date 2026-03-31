import EmptyState from '@/Components/Shared/EmptyState';
import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusConfig = {
    sent:      { color: 'bg-blue-100 text-blue-800',    label: 'Awaiting Signature' },
    signed:    { color: 'bg-green-100 text-green-800',  label: 'Signed' },
    expired:   { color: 'bg-orange-100 text-orange-800', label: 'Expired' },
    cancelled: { color: 'bg-red-100 text-red-800',      label: 'Cancelled' },
};

function StatusBadge({ status }) {
    const cfg = statusConfig[status] ?? { color: 'bg-gray-100 text-gray-700', label: status };
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cfg.color}`}>
            {cfg.label}
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

export default function Contracts({ client, contracts }) {
    return (
        <PortalLayout client={client}>
            <div className="max-w-5xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Contracts</h1>

                {contracts.length === 0 ? (
                    <EmptyState
                        icon="📝"
                        title="No contracts yet"
                        description="Contracts sent for your signature will appear here."
                    />
                ) : (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contract #</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Value</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Starts</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Signed On</th>
                                    <th className="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {contracts.map(c => (
                                    <tr key={c.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 text-sm font-medium text-gray-900">{c.number}</td>
                                        <td className="px-5 py-3 text-sm text-gray-700">{c.title}</td>
                                        <td className="px-5 py-3"><StatusBadge status={c.status} /></td>
                                        <td className="px-5 py-3 text-sm text-gray-900">{fmt(c.value, c.currency)}</td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{fmtDate(c.starts_at)}</td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{fmtDate(c.signed_at)}</td>
                                        <td className="px-5 py-3 text-right">
                                            <Link
                                                href={route('portal.contracts.show', c.id)}
                                                className="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800"
                                            >
                                                {c.status === 'sent' ? 'Sign →' : 'View →'}
                                            </Link>
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
