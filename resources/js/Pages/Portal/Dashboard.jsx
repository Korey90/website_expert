import PortalLayout from '@/Layouts/PortalLayout';
import usePortalTrans from '@/Hooks/usePortalTrans';
import { Link, usePage } from '@inertiajs/react';

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

const eventIcons = {
    'lead.created':            { icon: '📥', color: 'bg-blue-100 text-blue-600' },
    'project.created':         { icon: '📁', color: 'bg-green-100 text-green-600' },
    'project.status_changed':  { icon: '🔄', color: 'bg-yellow-100 text-yellow-600' },
    'invoice.sent':            { icon: '🧾', color: 'bg-blue-100 text-blue-600' },
    'invoice.paid':            { icon: '✅', color: 'bg-green-100 text-green-600' },
    'quote.sent':              { icon: '📋', color: 'bg-yellow-100 text-yellow-600' },
    'quote.accepted':          { icon: '🤝', color: 'bg-green-100 text-green-600' },
    'contract.signed':         { icon: '✍️', color: 'bg-purple-100 text-purple-600' },
};

function TimelineItem({ item }) {
    const meta = eventIcons[item.event_type] ?? { icon: '📌', color: 'bg-gray-100 text-gray-600' };
    const date = new Date(item.created_at);
    const dateStr = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    return (
        <div className="flex items-start gap-3">
            <div className={`w-9 h-9 rounded-full flex items-center justify-center text-base shrink-0 ${meta.color}`}>
                {meta.icon}
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate">{item.title}</p>
                {item.description && (
                    <p className="text-xs text-gray-500 truncate">{item.description}</p>
                )}
            </div>
            <span className="text-xs text-gray-400 shrink-0 pt-0.5">{dateStr}</span>
        </div>
    );
}

export default function Dashboard({ client, projects, invoices, quotes, timeline = [] }) {
    const t = usePortalTrans();
    const { flash = {} } = usePage().props;

    const pendingInvoices = invoices.filter(i => ['sent', 'overdue'].includes(i.status));
    const activeProjects  = projects.filter(p => p.status === 'active');
    const pendingQuotes   = quotes.filter(q => q.status === 'sent');

    if (!client) {
        return (
            <PortalLayout client={null}>
                <div className="max-w-2xl mx-auto mt-16 text-center space-y-6">
                    <div className="w-16 h-16 rounded-full bg-yellow-50 flex items-center justify-center text-3xl mx-auto">⏳</div>
                    <h1 className="text-2xl font-bold text-gray-900">Account activation pending</h1>
                    <p className="text-gray-500">
                        Your account has been created. Our team will link your client profile shortly.
                        You will receive an email confirmation once your portal is fully activated.
                    </p>
                    <p className="text-sm text-gray-400">
                        Questions? Contact us at <a href="mailto:hello@websiteexpert.co.uk" className="text-red-600 hover:underline">hello@websiteexpert.co.uk</a>
                    </p>
                </div>
            </PortalLayout>
        );
    }

    return (
        <PortalLayout client={client}>
            <div className="max-w-6xl mx-auto space-y-8">

                {flash.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                {flash.warning && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        {flash.warning}
                    </div>
                )}

                {flash.success && (
                    <div className="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        {t('welcome_back', { name: client?.primary_contact_name || client?.company_name })}
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">{t('account_summary')}</p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <SummaryCard title={t('active_projects')} value={activeProjects.length}  icon="📁" colorClass="bg-blue-50 text-blue-600" />
                    <SummaryCard title={t('open_invoices')}   value={pendingInvoices.length}  icon="🧾" colorClass="bg-red-50 text-red-600" />
                    <SummaryCard title={t('pending_quotes')}  value={pendingQuotes.length}    icon="📋" colorClass="bg-yellow-50 text-yellow-600" />
                    <SummaryCard title={t('total_projects')}  value={projects.length}          icon="✅" colorClass="bg-green-50 text-green-600" />
                </div>

                {/* Recent Projects */}
                <section>
                    <div className="flex justify-between items-center mb-3">
                        <h2 className="text-lg font-semibold text-gray-800">{t('recent_projects')}</h2>
                        <Link href={route('portal.projects')} className="text-sm text-red-600 hover:underline">{t('view_all')}</Link>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {projects.length === 0 ? (
                            <div className="p-6 text-sm text-gray-500 text-center">{t('no_projects')}</div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_project')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_status')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{t('col_progress')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">{t('col_deadline')}</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {projects.map(p => {
                                        const pct = p.tasks_count > 0 ? Math.round((p.tasks_done_count / p.tasks_count) * 100) : null;
                                        return (
                                            <tr key={p.id} className="hover:bg-gray-50">
                                                <td className="px-5 py-3">
                                                    <Link href={route('portal.projects.show', p.id)} className="text-sm font-medium text-red-600 hover:underline">
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
                        <h2 className="text-lg font-semibold text-gray-800">{t('recent_invoices')}</h2>
                        <Link href={route('portal.invoices')} className="text-sm text-red-600 hover:underline">{t('view_all')}</Link>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        {invoices.length === 0 ? (
                            <div className="p-6 text-sm text-gray-500 text-center">{t('no_invoices')}</div>
                        ) : (
                            <table className="min-w-full divide-y divide-gray-100">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_number')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_status')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_amount_due')}</th>
                                        <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('col_due_date')}</th>
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

                {/* Activity Timeline */}
                <section>
                    <h2 className="text-lg font-semibold text-gray-800 mb-3">{t('recent_activity')}</h2>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        {timeline.length === 0 ? (
                            <p className="text-sm text-gray-500 text-center py-4">{t('no_activity')}</p>
                        ) : (
                            <div className="space-y-4">
                                {timeline.map(item => (
                                    <TimelineItem key={item.id} item={item} />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

            </div>
        </PortalLayout>
    );
}
