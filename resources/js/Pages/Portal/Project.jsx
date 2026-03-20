import PortalLayout from '@/Layouts/PortalLayout';
import { useForm } from '@inertiajs/react';

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

function MessageBubble({ message, clientId }) {
    const isClient = message.sender_type?.includes('Client') && message.sender_id === clientId;

    return (
        <div className={`flex ${isClient ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm
                ${isClient ? 'bg-red-600 text-white rounded-br-sm' : 'bg-white text-gray-800 border border-gray-200 rounded-bl-sm'}`}>
                <p className="whitespace-pre-wrap">{message.content}</p>
                <p className={`text-xs mt-1 ${isClient ? 'text-red-200' : 'text-gray-400'}`}>
                    {new Date(message.created_at).toLocaleString()}
                    {!isClient && message.sender_type?.includes('User') && ' · Team'}
                </p>
            </div>
        </div>
    );
}

export default function Project({ client, project }) {
    const { data, setData, post, processing, reset, errors } = useForm({ content: '' });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('portal.messages.store', project.id), {
            onSuccess: () => reset(),
        });
    };

    return (
        <PortalLayout client={client}>
            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{project.title}</h1>
                        {project.service_type && (
                            <p className="text-sm text-gray-500 mt-1">{project.service_type}</p>
                        )}
                    </div>
                    <StatusBadge status={project.status} />
                </div>

                {/* Project details */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Details</h2>
                    <dl className="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                        {project.start_date && (
                            <div>
                                <dt className="text-gray-500">Start Date</dt>
                                <dd className="text-gray-900 font-medium">{project.start_date}</dd>
                            </div>
                        )}
                        {project.deadline && (
                            <div>
                                <dt className="text-gray-500">Deadline</dt>
                                <dd className="text-gray-900 font-medium">{project.deadline}</dd>
                            </div>
                        )}
                        {project.budget && (
                            <div>
                                <dt className="text-gray-500">Budget</dt>
                                <dd className="text-gray-900 font-medium">
                                    {project.currency ?? '£'}{parseFloat(project.budget).toLocaleString()}
                                </dd>
                            </div>
                        )}
                    </dl>
                    {project.description && (
                        <p className="mt-4 text-sm text-gray-600 border-t border-gray-100 pt-4">{project.description}</p>
                    )}
                </div>

                {/* Phases */}
                {project.project_phases?.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Progress</h2>
                        <div className="space-y-3">
                            {project.project_phases.map(phase => {
                                const total = phase.tasks?.length ?? 0;
                                const done  = phase.tasks?.filter(t => t.status === 'done').length ?? 0;
                                const pct   = total > 0 ? Math.round((done / total) * 100) : 0;

                                return (
                                    <div key={phase.id}>
                                        <div className="flex justify-between text-sm mb-1">
                                            <span className="font-medium text-gray-700">{phase.name}</span>
                                            <span className="text-gray-500">{done}/{total} tasks</span>
                                        </div>
                                        <div className="w-full bg-gray-100 rounded-full h-2">
                                            <div
                                                className="bg-red-500 h-2 rounded-full transition-all"
                                                style={{ width: `${pct}%` }}
                                            />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}

                {/* Messages */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden" style={{ minHeight: '400px' }}>
                    <div className="px-5 py-4 border-b border-gray-100">
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">Messages</h2>
                    </div>

                    <div className="flex-1 overflow-y-auto p-5 space-y-3" style={{ maxHeight: '400px' }}>
                        {(!project.messages || project.messages.length === 0) ? (
                            <p className="text-sm text-gray-400 text-center py-8">No messages yet. Start the conversation below.</p>
                        ) : (
                            project.messages.map(msg => (
                                <MessageBubble key={msg.id} message={msg} clientId={client?.id} />
                            ))
                        )}
                    </div>

                    <form onSubmit={handleSubmit} className="border-t border-gray-100 p-4 flex gap-3">
                        <textarea
                            value={data.content}
                            onChange={e => setData('content', e.target.value)}
                            placeholder="Type your message…"
                            rows={2}
                            className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-400"
                            onKeyDown={e => {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    handleSubmit(e);
                                }
                            }}
                        />
                        <button
                            type="submit"
                            disabled={processing || !data.content.trim()}
                            className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors self-end"
                        >
                            Send
                        </button>
                    </form>
                    {errors.content && <p className="px-4 pb-3 text-xs text-red-600">{errors.content}</p>}
                </div>
            </div>
        </PortalLayout>
    );
}
