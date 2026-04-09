import { Head, Link, router, usePage } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import StatusBadge from '@/Components/LandingPage/StatusBadge';

function StatCard({ label, value, sub, colorCls = 'text-gray-900 dark:text-white' }) {
    return (
        <div className="rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-5 py-4 shadow-sm">
            <div className={`text-2xl font-bold ${colorCls}`}>{value}</div>
            {sub && <div className="text-xs text-gray-400 dark:text-gray-500">{sub}</div>}
            <div className="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1">{label}</div>
        </div>
    );
}

export default function Show({ page, recentLeads = [], client }) {
    const { flash } = usePage().props;

    const cvr = page.views_count > 0
        ? ((page.conversions_count / page.views_count) * 100).toFixed(2)
        : '0.00';

    const handlePublish = () => router.post(route('landing-pages.publish', page.id));
    const handleUnpublish = () => router.post(route('landing-pages.unpublish', page.id));

    return (
        <PortalLayout client={client}>
            <Head title={'Stats - ' + page.title} />

            <div className="py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                    <div className="flex items-center gap-3 flex-wrap">
                        <Link
                            href={route('landing-pages.index')}
                            className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h2 className="font-bold text-xl text-gray-900 dark:text-white">{page.title}</h2>
                                <StatusBadge status={page.status} />
                            </div>
                            <p className="text-xs text-gray-400 dark:text-gray-500">/lp/{page.slug}</p>
                        </div>
                        <div className="ml-auto flex items-center gap-2">
                            <Link
                                href={route('landing-pages.edit', page.id)}
                                className="rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 font-semibold text-sm px-4 py-2 transition"
                            >
                                Edit
                            </Link>
                            {page.status === 'published' ? (
                                <>
                                    <a
                                        href={page.public_url ?? route('lp.show', page.slug)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-100 font-semibold text-sm px-4 py-2 transition"
                                    >
                                        View Live
                                    </a>
                                    <button
                                        type="button"
                                        onClick={handleUnpublish}
                                        className="rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 font-semibold text-sm px-4 py-2 transition"
                                    >
                                        Unpublish
                                    </button>
                                </>
                            ) : (
                                <button
                                    type="button"
                                    onClick={handlePublish}
                                    className="rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-4 py-2 transition"
                                >
                                    Publish
                                </button>
                            )}
                        </div>
                    </div>

                    {flash && flash.success && (
                        <div className="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
                            {flash.success}
                        </div>
                    )}

                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <StatCard label="Views" value={(page.views_count ?? 0).toLocaleString()} />
                        <StatCard
                            label="Leads captured"
                            value={(page.conversions_count ?? 0).toLocaleString()}
                            colorCls="text-brand-600 dark:text-brand-400"
                        />
                        <StatCard
                            label="Conversion rate"
                            value={cvr + '%'}
                            colorCls="text-green-600 dark:text-green-400"
                        />
                        <StatCard
                            label="Published"
                            value={page.published_at ? new Date(page.published_at).toLocaleDateString() : 'n/a'}
                            sub={page.status !== 'published' ? 'Not published' : null}
                        />
                    </div>

                    <div className="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div className="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 className="font-semibold text-sm text-gray-900 dark:text-white">Recent leads</h3>
                            <span className="text-xs text-gray-400 dark:text-gray-500">Last 20</span>
                        </div>

                        {recentLeads.length === 0 ? (
                            <div className="py-12 text-center">
                                <p className="text-gray-400 dark:text-gray-500 text-sm">No leads yet. Publish the page to start capturing.</p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Phone</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Source</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                            <th className="px-5 py-3" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                        {recentLeads.map((lead, i) => (
                                            <tr key={lead.id ?? i} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                <td className="px-5 py-3 text-sm text-gray-900 dark:text-white">{lead.name || 'n/a'}</td>
                                                <td className="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">{lead.email || 'n/a'}</td>
                                                <td className="px-5 py-3 text-sm text-gray-600 dark:text-gray-300">{lead.phone || 'n/a'}</td>
                                                <td className="px-5 py-3 text-xs text-gray-400 dark:text-gray-500">{lead.utm_source || 'direct'}</td>
                                                <td className="px-5 py-3 text-xs text-gray-400 dark:text-gray-500">{lead.created_at || 'n/a'}</td>
                                                <td className="px-5 py-3 text-right">
                                                    {lead.id && (
                                                        <Link
                                                            href={route('portal.leads.show', lead.id)}
                                                            className="text-xs text-brand-600 dark:text-brand-400 hover:underline font-medium"
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

                </div>
            </div>
        </PortalLayout>
    );
}
