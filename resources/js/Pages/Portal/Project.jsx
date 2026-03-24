import PortalLayout from '@/Layouts/PortalLayout';
import { useForm } from '@inertiajs/react';

const projectStatusColors = {
    planning:  'bg-blue-100 text-blue-800',
    active:    'bg-green-100 text-green-800',
    on_hold:   'bg-yellow-100 text-yellow-800',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-800',
};

const phaseStatusConfig = {
    pending:     { label: 'Pending',     dot: 'bg-gray-400',   text: 'text-gray-500',   bar: 'bg-gray-300' },
    in_progress: { label: 'In Progress', dot: 'bg-yellow-400', text: 'text-yellow-600', bar: 'bg-yellow-400' },
    completed:   { label: 'Completed',   dot: 'bg-green-500',  text: 'text-green-600',  bar: 'bg-green-500' },
    cancelled:   { label: 'Cancelled',   dot: 'bg-red-400',    text: 'text-red-500',    bar: 'bg-red-400' },
};

function StatusBadge({ status }) {
    const cls = projectStatusColors[status] ?? 'bg-gray-100 text-gray-700';
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

    const phases      = project.phases ?? [];
    const allTasks    = phases.flatMap(p => p.tasks ?? []);
    const totalTasks  = allTasks.length;
    const doneTasks   = allTasks.filter(t => t.status === 'done').length;
    const totalPhases = phases.length;
    const donePhases  = phases.filter(p => p.status === 'completed').length;
    const overallPct  = totalTasks > 0 ? Math.round((doneTasks / totalTasks) * 100) : 0;
    const currency    = project.currency === 'EUR' ? '€' : project.currency === 'USD' ? '$' : project.currency === 'PLN' ? 'zł' : '£';

    return (
        <PortalLayout client={client}>
            <div className="max-w-4xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{project.title}</h1>
                        {project.service_type && (
                            <p className="text-sm text-gray-500 mt-1 capitalize">{project.service_type?.replace('_', ' ')}</p>
                        )}
                    </div>
                    <StatusBadge status={project.status} />
                </div>

                {/* Overview stats */}
                {totalTasks > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <div className="grid grid-cols-3 divide-x divide-gray-100 mb-4">
                            <div className="pr-4 text-center">
                                <div className="text-2xl font-bold text-gray-900">{overallPct}%</div>
                                <div className="text-xs text-gray-500 mt-0.5">Overall progress</div>
                            </div>
                            <div className="px-4 text-center">
                                <div className="text-2xl font-bold text-gray-900">{doneTasks}<span className="text-gray-400 text-base font-normal">/{totalTasks}</span></div>
                                <div className="text-xs text-gray-500 mt-0.5">Tasks done</div>
                            </div>
                            <div className="pl-4 text-center">
                                <div className="text-2xl font-bold text-gray-900">{donePhases}<span className="text-gray-400 text-base font-normal">/{totalPhases}</span></div>
                                <div className="text-xs text-gray-500 mt-0.5">Phases done</div>
                            </div>
                        </div>
                        <div className="w-full bg-gray-100 rounded-full h-3">
                            <div
                                className="bg-red-500 h-3 rounded-full transition-all duration-500"
                                style={{ width: `${overallPct}%` }}
                            />
                        </div>
                    </div>
                )}

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
                                    {currency}{parseFloat(project.budget).toLocaleString()}
                                </dd>
                            </div>
                        )}
                    </dl>
                    {project.description && (
                        <p className="mt-4 text-sm text-gray-600 border-t border-gray-100 pt-4">{project.description}</p>
                    )}
                </div>

                {/* Phases */}
                {phases.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">Project Phases</h2>
                        <div className="space-y-3">
                            {phases.map(phase => {
                                const cfg      = phaseStatusConfig[phase.status] ?? phaseStatusConfig['pending'];
                                const total    = phase.tasks?.length ?? 0;
                                const done     = phase.tasks?.filter(t => t.status === 'done').length ?? 0;
                                const pct      = total > 0 ? Math.round((done / total) * 100) : 0;

                                return (
                                    <div key={phase.id} className="rounded-lg border border-gray-100 p-4">
                                        <div className="flex items-center justify-between gap-3 mb-2">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className={`flex-shrink-0 w-2 h-2 rounded-full ${cfg.dot}`} />
                                                <span className="font-medium text-gray-800 text-sm truncate">{phase.name}</span>
                                            </div>
                                            <div className="flex items-center gap-3 flex-shrink-0">
                                                {total > 0 && (
                                                    <span className="text-xs text-gray-400">{done}/{total} tasks</span>
                                                )}
                                                <span className={`text-xs font-semibold ${cfg.text}`}>{cfg.label}</span>
                                            </div>
                                        </div>
                                        {total > 0 && (
                                            <div className="w-full bg-gray-100 rounded-full h-1.5">
                                                <div
                                                    className={`${cfg.bar} h-1.5 rounded-full transition-all duration-500`}
                                                    style={{ width: `${pct}%` }}
                                                />
                                            </div>
                                        )}
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

