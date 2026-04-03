import { useState } from 'react';

const ACTIVITY_CONFIG = {
    created:        { icon: 'plus',     color: 'bg-green-500',  label: 'Lead Created' },
    stage_moved:    { icon: 'arrow',    color: 'bg-blue-500',   label: 'Stage Changed' },
    marked_won:     { icon: 'trophy',   color: 'bg-emerald-500',label: 'Won' },
    marked_lost:    { icon: 'x',        color: 'bg-red-500',    label: 'Lost' },
    assigned:       { icon: 'user',     color: 'bg-purple-500', label: 'Assigned' },
    email_sent:     { icon: 'envelope', color: 'bg-blue-400',   label: 'Email Sent' },
    note_updated:   { icon: 'pencil',   color: 'bg-gray-500',   label: 'Note Updated' },
    project_created:{ icon: 'folder',   color: 'bg-indigo-500', label: 'Project Created' },
    deleted:        { icon: 'trash',    color: 'bg-red-400',    label: 'Deleted' },
    restored:       { icon: 'refresh',  color: 'bg-teal-500',   label: 'Restored' },
};

function ActivityIcon({ type }) {
    const cls = 'h-4 w-4 text-white';

    switch (type) {
        case 'plus':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>;
        case 'arrow':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>;
        case 'trophy':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>;
        case 'x':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}><path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>;
        case 'user':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>;
        case 'envelope':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>;
        case 'pencil':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>;
        case 'folder':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>;
        case 'trash':
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>;
        default:
            return <svg className={cls} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>;
    }
}

function formatRelativeTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const PAGE_SIZE = 20;

/**
 * LeadTimeline — chronologiczna historia aktywności leada.
 *
 * @param {{ activities: Array }} props
 */
export default function LeadTimeline({ activities = [] }) {
    const [visible, setVisible] = useState(PAGE_SIZE);

    if (!activities.length) {
        return (
            <p className="text-sm text-gray-500 dark:text-gray-400 py-4">No activity recorded yet.</p>
        );
    }

    const items = activities.slice(0, visible);

    return (
        <div className="flow-root">
            <ul className="-mb-8">
                {items.map((activity, idx) => {
                    const cfg = ACTIVITY_CONFIG[activity.type] ?? ACTIVITY_CONFIG.note_updated;
                    const isLast = idx === items.length - 1;

                    return (
                        <li key={activity.id}>
                            <div className="relative pb-8">
                                {!isLast && (
                                    <span
                                        className="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"
                                        aria-hidden="true"
                                    />
                                )}
                                <div className="relative flex space-x-3">
                                    <div>
                                        <span className={`h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-900 ${cfg.color}`}>
                                            <ActivityIcon type={cfg.icon} />
                                        </span>
                                    </div>
                                    <div className="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                                {activity.description}
                                            </p>
                                            {activity.user && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                    by {activity.user.name}
                                                </p>
                                            )}
                                        </div>
                                        <div className="whitespace-nowrap text-right text-xs text-gray-400 dark:text-gray-500" title={activity.created_at}>
                                            {formatRelativeTime(activity.created_at)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ul>

            {activities.length > visible && (
                <div className="mt-4 text-center">
                    <button
                        type="button"
                        onClick={() => setVisible(v => v + PAGE_SIZE)}
                        className="text-sm text-brand-600 dark:text-brand-400 hover:underline"
                    >
                        Load more ({activities.length - visible} remaining)
                    </button>
                </div>
            )}
        </div>
    );
}
