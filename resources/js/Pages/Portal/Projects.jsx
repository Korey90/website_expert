import PortalLayout from '@/Layouts/PortalLayout';
import { Link } from '@inertiajs/react';

const statusColors = {
    planning:  'bg-blue-100 text-blue-800',
    active:    'bg-green-100 text-green-800',
    on_hold:   'bg-yellow-100 text-yellow-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-800',
};

function StatusBadge({ status }) {
    const cls = statusColors[status] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${cls}`}>
            {status?.replace('_', ' ')}
        </span>
    );
}

export default function Projects({ client, projects }) {
    return (
        <PortalLayout client={client}>
            <div className="max-w-5xl mx-auto space-y-6">
                <h1 className="text-2xl font-bold text-gray-900">Your Projects</h1>

                {projects.length === 0 ? (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div className="text-4xl mb-4">📁</div>
                        <p className="text-gray-500">No projects found.</p>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {projects.map(project => (
                            <Link
                                key={project.id}
                                href={route('portal.project', project.id)}
                                className="block bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:border-red-300 hover:shadow transition-all"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1 min-w-0">
                                        <h2 className="text-base font-semibold text-gray-900 truncate">{project.title}</h2>
                                        {project.service_type && (
                                            <p className="text-sm text-gray-500 mt-0.5">{project.service_type}</p>
                                        )}
                                    </div>
                                    <StatusBadge status={project.status} />
                                </div>

                                <div className="mt-3 flex flex-wrap gap-4 text-sm text-gray-500">
                                    {project.start_date && (
                                        <span>Started: <span className="text-gray-700">{project.start_date}</span></span>
                                    )}
                                    {project.deadline && (
                                        <span>Deadline: <span className="text-gray-700">{project.deadline}</span></span>
                                    )}
                                    {project.budget && (
                                        <span>Budget: <span className="text-gray-700 font-medium">
                                            {project.currency ?? '£'}{parseFloat(project.budget).toLocaleString()}
                                        </span></span>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
