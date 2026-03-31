import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

function StatCard({ label, value, href, color = 'indigo' }) {
    const colors = {
        indigo: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        amber:  'bg-amber-50 text-amber-700 border-amber-200',
        red:    'bg-red-50 text-red-700 border-red-200',
    };

    const card = (
        <div className={`rounded-lg border p-5 ${colors[color]}`}>
            <p className="text-sm font-medium opacity-75">{label}</p>
            <p className="mt-1 text-3xl font-bold">{value}</p>
        </div>
    );

    return href ? <Link href={href}>{card}</Link> : card;
}

export default function Dashboard({ activeProjects, openLeads, unpaidInvoices, upcomingDeadlines }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

                    {/* Stat Cards */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <StatCard
                            label="Active Projects"
                            value={activeProjects}
                            href="/admin/projects"
                            color="indigo"
                        />
                        <StatCard
                            label="Open Leads"
                            value={openLeads}
                            href="/admin/leads"
                            color="amber"
                        />
                        <StatCard
                            label="Unpaid Invoices"
                            value={unpaidInvoices}
                            href="/admin/invoices"
                            color="red"
                        />
                    </div>

                    {/* Upcoming Deadlines */}
                    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 px-5 py-4">
                            <h3 className="text-base font-semibold text-gray-900">
                                Deadlines — next 7 days
                            </h3>
                        </div>
                        {upcomingDeadlines.length === 0 ? (
                            <p className="px-5 py-8 text-center text-sm text-gray-500">
                                No deadlines in the next 7 days.
                            </p>
                        ) : (
                            <ul className="divide-y divide-gray-100">
                                {upcomingDeadlines.map((project) => (
                                    <li
                                        key={project.id}
                                        className="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50"
                                    >
                                        <a
                                            href={`/admin/projects/${project.id}`}
                                            className="font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {project.title}
                                        </a>
                                        <span className="ml-4 shrink-0 text-gray-500">
                                            {new Date(project.deadline).toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'short',
                                            })}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {/* Admin Panel shortcut */}
                    <div className="text-right">
                        <a
                            href="/admin"
                            className="inline-flex items-center gap-1 rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                        >
                            Open Admin Panel →
                        </a>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}

