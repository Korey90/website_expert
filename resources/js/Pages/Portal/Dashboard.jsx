import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusColors = {
    // Projects
    planning:    'bg-blue-100 text-blue-800',
    active:      'bg-green-100 text-green-800',
    on_hold:     'bg-yellow-100 text-yellow-800',
    completed:   'bg-gray-100 text-gray-700',
    cancelled:   'bg-red-100 text-red-800',
    // Invoices
    draft:       'bg-gray-100 text-gray-700',
    sent:        'bg-blue-100 text-blue-800',
    paid:        'bg-green-100 text-green-800',
    overdue:     'bg-red-100 text-red-800',
    // Quotes
    accepted:    'bg-green-100 text-green-800',
    rejected:    'bg-red-100 text-red-800',
    expired:     'bg-orange-100 text-orange-800',
};

function StatusBadge({ status }) {
    const cls = statusColors[status] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${cls}`}>
            {status?.replace('_', ' ')}
        </span>
    );
}

function SummaryCard({ title, value, icon, colorClass }) {
    return (
        <div className={`bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center gap-4`}>
            <div className={`w-12 h-12 rounded-full flex items-center justify-center text-xl ${colorClass}`}>
                {icon}
            </div>
            <div>
                <div className="text-2xl font-bold text-gray-900">{value}</div>
                <div className="text-sm text-gray-500">{title}</div>
            </div>
        </div>
    );
}

export default function Dashboard({ client, projects, invoices, quotes }) {
    const pendingInvoices = invoices.filter(i => ['sent', 'overdue'].includes(i.status));
    const activeProjects = projects.filter(p => p.status === 'active');
    const pendingQuotes  = quotes.filter(q => q.status === 'sent');

    return (
        <PortalLayout client={client}>
            <div className="max-w-6xl mx-auto space-y-8">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        Welcome back, {client?.primary_contact_name || client?.company_name}
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">Here's a summary of your account.</p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <SummaryCard title="Active Projects"   value={activeProjects.length}  icon="📁" colorClass="bg-blue-50 text-blue-600" />
                    <SummaryCard title="Open Invoices"     value={pendingInvoices.length}  icon="🧾" colorClass="bg-red-50 text-red-600" />
                    <SummaryCard title="Pending Quotes"    value={pendingQuotes.length}    icon="📋" colorClass="bg-yellow-50 text-yellow-600" />
                    <SummaryCard title="Total Projects"    value={projects.length}          icon="✅" colorClass="bg-green-50 text-green-600" />
                </div>

                {/* Recent Projects */}
                <section>
                    <div className="flex justify-between items-center mb-3">
                        <h2 className="text-lg font-semibold text-gray-800">Recent Projects</h2>
                        <Link href={route('portal.projects')} className="text-sm text-red-600 hover:underline">View all →</Link>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {projects.length === 0 ? (
                            <div className="p-6 text-sm text-gray-500 text-center">No projects yet.</div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Progress</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Deadline</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {projects.map(p => {
                                        const pct = p.tasks_count > 0 ? Math.round((p.tasks_done_count / p.tasks_count) * 100) : null;
                                        return (
                                            <tr key={p.id} className="hover:bg-gray-50">
                                                <td className="px-5 py-3">
                                                    <Link href={route('portal.project', p.id)} className="text-sm font-medium text-red-600 hover:underline">
                                                        {p.title}
                                                    </Link>
                                                </td>
                                                <td className="px-5 py-3"><StatusBadge status={p.status} /></td>
                                                <td className="px-5 py-3 hidden sm:table-cell">
                                                    {pct !== null ? (
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex-1 bg-gray-100 rounded-full h-1.5 w-24">
                                                                <div className="bg-red-500 h-1.5 rounded-full" style={{ width: `${pct}%` }} />
                                                            </div>
                                                            <span className="text-xs text-gray-500 w-8 text-right">{pct}%</span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-xs text-gray-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 text-sm text-gray-600 hidden sm:table-cell">{p.deadline ?? '—'}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        )}
                    </div>
                </section>

                {/* Recent Invoices */}
                <section>
                    <div className="flex justify-between items-center mb-3">
                        <h2 className="text-lg font-semibold text-gray-800">Recent Invoices</h2>
                        <Link href={route('portal.invoices')} className="text-sm text-red-600 hover:underline">View all →</Link>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {invoices.length === 0 ? (
                            <div className="p-6 text-sm text-gray-500 text-center">No invoices yet.</div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount Due</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {invoices.map(inv => (
                                        <tr key={inv.id} className="hover:bg-gray-50">
                                            <td className="px-5 py-3 text-sm font-medium text-gray-900">{inv.number}</td>
                                            <td className="px-5 py-3"><StatusBadge status={inv.status} /></td>
                                            <td className="px-5 py-3 text-sm text-gray-900">
                                                £{parseFloat(inv.amount_due).toFixed(2)}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-gray-600">{inv.due_date ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </section>
            </div>
        </PortalLayout>
    );
}
