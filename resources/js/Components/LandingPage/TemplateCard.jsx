/**
 * Clickable card used in the Create LP template selector.
 * @param {{ template: { key: string, label: string, description: string, icon: string, sections: string[] }, selected: boolean, onSelect: (key: string) => void }} props
 */
export default function TemplateCard({ template, selected, onSelect }) {
    const SECTION_ICONS = {
        hero: '🦸', features: '✅', testimonials: '💬', cta: '🎯',
        form: '📬', faq: '❓', text: '📄', video: '▶️',
    };

    return (
        <button
            type="button"
            onClick={() => onSelect(template.key)}
            className={[
                'group relative w-full rounded-xl border-2 p-4 text-left transition-all duration-150',
                'hover:border-brand-400 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                selected
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/30 shadow-md ring-1 ring-brand-500'
                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800',
            ].join(' ')}
        >
            {selected && (
                <span className="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-brand-500 text-white">
                    <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </span>
            )}

            <p className="mb-1 text-sm font-semibold text-gray-900 dark:text-white">
                {template.label}
            </p>
            <p className="mb-3 text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                {template.description}
            </p>

            {/* Section pills */}
            {template.sections?.length > 0 && (
                <div className="flex flex-wrap gap-1">
                    {template.sections.map((s) => (
                        <span
                            key={s}
                            className="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-medium
                                       bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                        >
                            {SECTION_ICONS[s] ?? '▪️'} {s}
                        </span>
                    ))}
                </div>
            )}
        </button>
    );
}
