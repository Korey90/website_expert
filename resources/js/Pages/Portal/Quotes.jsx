import PortalLayout from '@/Layouts/PortalLayout';

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

export default function Quotes({ client, quotes }) {
    return (
        <PortalLayout client={client}>
            <div className="max-w-5xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Quotes</h1>

                {quotes.length === 0 ? (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div className="text-4xl mb-4">📋</div>
                        <p className="text-gray-500">No quotes found.</p>
                    </div>
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
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {quotes.map(q => (
                                    <tr key={q.id} className="hover:bg-gray-50">
                                        <td className="px-5 py-3 text-sm font-medium text-gray-900">{q.number}</td>
                                        <td className="px-5 py-3"><StatusBadge status={q.status} /></td>
                                        <td className="px-5 py-3 text-sm text-gray-900">
                                            {q.currency ?? '£'}{parseFloat(q.total).toFixed(2)}
                                        </td>
                                        <td className="px-5 py-3 text-sm text-gray-600">{q.valid_until ?? '—'}</td>
                                        <td className="px-5 py-3 text-sm text-gray-600">
                                            {q.accepted_at
                                                ? new Date(q.accepted_at).toLocaleDateString()
                                                : '—'}
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
