import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import StatusBadge from '@/Components/LandingPage/StatusBadge';

function StatsCard({ label, value, colorCls = 'text-gray-900 dark:text-white' }) {
    return (
        <div className="rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 px-5 py-4 shadow-sm">
            <div className={`text-2xl font-bold ${colorCls}`}>{value}</div>
            <div className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{label}</div>
        </div>
    );
}

export default function Index({ landingPages, stats = {}, client }) {
    const { flash } = usePage().props;
    const [deleting, setDeleting] = useState(null);

    const pages = landingPages?.data ?? landingPages ?? [];
    const paginationLinks = landingPages?.links ?? [];

    const handlePublish = (id) => {
        router.post(route('landing-pages.publish', id), {}, { preserveScroll: true });
    };

    const handleUnpublish = (id) => {
        router.post(route('landing-pages.unpublish', id), {}, { preserveScroll: true });
    };

    const handleDelete = (id) => {
        if (!confirm('Are you sure you want to delete this landing page?')) return;
        setDeleting(id);
        router.delete(route('landing-pages.destroy', id), {
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <PortalLayout client={client}>
            <Head title="Landing Pages" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Page header */}
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Landing Pages</h1>
                        <Link
                            href={route('landing-pages.create')}
                            className="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-4 py-2.5 transition focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                        >
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            New Landing Page
                        </Link>
                    </div>

                    {/* Flash messages */}
                    {flash?.success && (
                        <div className="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 text-sm">
                            {flash.error}
                        </div>
                    )}

                    {/* Stats row */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <StatsCard label="Total" value={stats.total ?? pages.length} />
                        <StatsCard label="Published" value={stats.published ?? 0} colorCls="text-green-600 dark:text-green-400" />
                        <StatsCard label="Drafts" value={stats.draft ?? 0} colorCls="text-gray-500 dark:text-gray-400" />
                        <StatsCard label="Archived" value={stats.archived ?? 0} colorCls="text-amber-600 dark:text-amber-400" />
                    </div>

                    {/* Table */}
                    <div className="overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                        {pages.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-20 gap-4 text-center px-4">
                                <span className="text-5xl">📄</span>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">No landing pages yet</h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                    Create your first landing page to start capturing leads.
                                </p>
                                <Link
                                    href={route('landing-pages.create')}
                                    className="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-4 py-2.5 transition"
                                >
                                    Create Landing Page
                                </Link>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Views</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Leads</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">CVR %</th>
                                            <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Updated</th>
                                            <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                        {pages.map((page) => {
                                            const cvr = page.views_count > 0
                                                ? ((page.conversions_count / page.views_count) * 100).toFixed(1)
                                                : '—';
                                            return (
                                                <tr key={page.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td className="px-5 py-3.5">
                                                        <div className="font-medium text-sm text-gray-900 dark:text-white">{page.title}</div>
                                                        <div className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">/{page.slug}</div>
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <StatusBadge status={page.status} />
                                                    </td>
                                                    <td className="px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                                        {page.views_count?.toLocaleString() ?? '0'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                                        {page.conversions_count?.toLocaleString() ?? '0'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-sm text-gray-700 dark:text-gray-300">
                                                        {cvr !== '—' ? `${cvr}%` : '—'}
                                                    </td>
                                                    <td className="px-5 py-3.5 text-xs text-gray-400 dark:text-gray-500">
                                                        {page.updated_at_human ?? page.updated_at}
                                                    </td>
                                                    <td className="px-5 py-3.5">
                                                        <div className="flex items-center justify-end gap-1.5 flex-wrap">
                                                            <Link
                                                                href={route('landing-pages.edit', page.id)}
                                                                className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                                            >
                                                                Edit
                                                            </Link>
                                                            <Link
                                                                href={route('landing-pages.show', page.id)}
                                                                className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                                                            >
                                                                Stats
                                                            </Link>
                                                            {page.status === 'published' ? (
                                                                <>
                                                                    <a
                                                                        href={page.public_url ?? route('lp.show', page.slug)}
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/50 transition"
                                                                    >
                                                                        Live ↗
                                                                    </a>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleUnpublish(page.id)}
                                                                        className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 transition"
                                                                    >
                                                                        Unpublish
                                                                    </button>
                                                                </>
                                                            ) : (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handlePublish(page.id)}
                                                                    className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-400 hover:bg-brand-100 transition"
                                                                >
                                                                    Publish
                                                                </button>
                                                            )}
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDelete(page.id)}
                                                                disabled={deleting === page.id}
                                                                className="rounded-lg px-2.5 py-1.5 text-xs font-medium bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 transition disabled:opacity-50"
                                                            >
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {paginationLinks.length > 3 && (
                        <div className="flex flex-wrap gap-1 justify-center">
                            {paginationLinks.map((link, i) => (
                                link.url === null ? (
                                    <span
                                        key={i}
                                        className="px-3 py-1.5 rounded-lg text-sm text-gray-400 dark:text-gray-600"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition ${link.active ? 'bg-brand-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    )}

                </div>
            </div>
        </PortalLayout>
    );
}
