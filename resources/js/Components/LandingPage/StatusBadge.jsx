const STATUS_CONFIG = {
    draft: {
        label: 'Draft',
        classes: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    },
    published: {
        label: 'Published',
        classes: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    },
    archived: {
        label: 'Archived',
        classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
};

/**
 * Renders a coloured pill badge for a landing page status.
 * @param {{ status: 'draft'|'published'|'archived', size?: 'sm'|'md' }} props
 */
export default function StatusBadge({ status, size = 'sm' }) {
    const config = STATUS_CONFIG[status] ?? STATUS_CONFIG.draft;

    const sizeClasses = size === 'md'
        ? 'px-3 py-1 text-sm font-medium'
        : 'px-2 py-0.5 text-xs font-medium';

    return (
        <span className={`inline-flex items-center rounded-full ${sizeClasses} ${config.classes}`}>
            {config.label}
        </span>
    );
}
