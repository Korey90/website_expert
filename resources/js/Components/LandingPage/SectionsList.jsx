import { useState } from 'react';
import { router } from '@inertiajs/react';

const SECTION_LABELS = {
    hero: 'Hero',
    features: 'Features',
    testimonials: 'Testimonials',
    cta: 'Call to Action',
    form: 'Lead Form',
    faq: 'FAQ',
    text: 'Text Block',
    video: 'Video',
};

const SECTION_ICONS = {
    hero: '🦸', features: '✅', testimonials: '💬', cta: '🎯',
    form: '📬', faq: '❓', text: '📄', video: '▶️',
};

/**
 * Inline text editor for a single section's content fields.
 */
function SectionContentEditor({ section, onChange }) {
    const c = section.content ?? {};

    const field = (key, label, type = 'text', rows = 2) => (
        <div key={key}>
            <label className="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{label}</label>
            {type === 'textarea' ? (
                <textarea
                    rows={rows}
                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                               text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                    value={c[key] ?? ''}
                    onChange={(e) => onChange({ ...c, [key]: e.target.value })}
                />
            ) : (
                <input
                    type="text"
                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                               text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                    value={c[key] ?? ''}
                    onChange={(e) => onChange({ ...c, [key]: e.target.value })}
                />
            )}
        </div>
    );

    switch (section.type) {
        case 'hero':
            return (
                <div className="space-y-3">
                    {field('headline', 'Headline')}
                    {field('subheadline', 'Sub-headline')}
                    {field('cta_text', 'Button Text')}
                    {field('cta_url', 'Button URL (or #form)')}
                </div>
            );
        case 'features':
            return (
                <div className="space-y-3">
                    {field('headline', 'Headline')}
                    <p className="text-xs text-gray-500 dark:text-gray-400 italic">
                        Edit individual feature items in the admin panel for full control.
                    </p>
                </div>
            );
        case 'testimonials':
            return (
                <div className="space-y-3">
                    {field('headline', 'Section Headline')}
                    <p className="text-xs text-gray-500 dark:text-gray-400 italic">
                        Edit testimonial items in the admin panel.
                    </p>
                </div>
            );
        case 'cta':
            return (
                <div className="space-y-3">
                    {field('headline', 'Headline')}
                    {field('subheadline', 'Sub-headline')}
                    {field('cta_text', 'Button Text')}
                    {field('cta_url', 'Button URL (or #form)')}
                </div>
            );
        case 'form':
            return (
                <div className="space-y-3">
                    {field('headline', 'Form Headline')}
                    {field('subheadline', 'Sub-headline')}
                    {field('cta_text', 'Submit Button Text')}
                    {field('success_message', 'Success Message', 'textarea', 2)}
                </div>
            );
        case 'faq':
            return (
                <div className="space-y-3">
                    {field('headline', 'Section Headline')}
                    <p className="text-xs text-gray-500 dark:text-gray-400 italic">
                        Edit FAQ items in the admin panel.
                    </p>
                </div>
            );
        case 'text':
            return (
                <div className="space-y-3">
                    {field('headline', 'Headline (optional)')}
                    {field('body', 'Body Text (HTML allowed)', 'textarea', 5)}
                </div>
            );
        case 'video':
            return (
                <div className="space-y-3">
                    {field('headline', 'Section Headline (optional)')}
                    {field('url', 'Video URL (YouTube / Vimeo)')}
                </div>
            );
        default:
            return null;
    }
}

/**
 * Accordion row for a single section.
 */
function SectionRow({ section, landingPageId, isFirst, isLast, onMoveUp, onMoveDown }) {
    const [open, setOpen] = useState(false);
    const [content, setContent] = useState(section.content ?? {});
    const [saving, setSaving] = useState(false);
    const [visible, setVisible] = useState(section.is_visible ?? true);

    const saveSection = () => {
        setSaving(true);
        router.patch(
            route('landing-pages.sections.update', { landingPage: landingPageId, section: section.id }),
            { content, is_visible: visible },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            }
        );
    };

    const deleteSection = () => {
        if (!window.confirm('Remove this section?')) return;
        router.delete(
            route('landing-pages.sections.destroy', { landingPage: landingPageId, section: section.id }),
            { preserveScroll: true }
        );
    };

    return (
        <div className="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            {/* Header row */}
            <div className={`flex items-center gap-3 px-4 py-3 ${open ? 'bg-gray-50 dark:bg-gray-800' : 'bg-white dark:bg-gray-850'}`}>
                {/* Move buttons */}
                <div className="flex flex-col gap-0.5 shrink-0">
                    <button
                        type="button"
                        disabled={isFirst}
                        onClick={onMoveUp}
                        className="rounded p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-25"
                        title="Move up"
                    >
                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        disabled={isLast}
                        onClick={onMoveDown}
                        className="rounded p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-25"
                        title="Move down"
                    >
                        <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </div>

                <span className="text-base">{SECTION_ICONS[section.type] ?? '▪'}</span>

                <button
                    type="button"
                    className="flex-1 text-left text-sm font-medium text-gray-800 dark:text-gray-200"
                    onClick={() => setOpen(!open)}
                >
                    {SECTION_LABELS[section.type] ?? section.type}
                </button>

                {/* Visible toggle */}
                <label className="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 cursor-pointer">
                    <input
                        type="checkbox"
                        className="h-3.5 w-3.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                        checked={visible}
                        onChange={(e) => setVisible(e.target.checked)}
                    />
                    Visible
                </label>

                {/* Delete */}
                <button
                    type="button"
                    onClick={deleteSection}
                    className="rounded p-1 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                    title="Remove section"
                >
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {/* Expand toggle */}
                <svg
                    className={`h-4 w-4 text-gray-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}
                    onClick={() => setOpen(!open)}
                    style={{ cursor: 'pointer' }}
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            {/* Expanded editor */}
            {open && (
                <div className="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-4 space-y-4">
                    <SectionContentEditor
                        section={section}
                        onChange={setContent}
                    />

                    <div className="flex justify-end">
                        <button
                            type="button"
                            onClick={saveSection}
                            disabled={saving}
                            className="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white
                                       hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-60 transition-colors"
                        >
                            {saving ? (
                                <>
                                    <svg className="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    Saving…
                                </>
                            ) : 'Save Section'}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

/**
 * Full sections list with reorder support.
 * @param {{ sections: Array, landingPageId: number }} props
 */
export default function SectionsList({ sections: initialSections, landingPageId }) {
    const [sections, setSections] = useState([...initialSections].sort((a, b) => a.order - b.order));
    const [addType, setAddType] = useState('hero');
    const [adding, setAdding] = useState(false);

    const moveSection = (index, direction) => {
        const newSections = [...sections];
        const swapIndex = index + direction;
        if (swapIndex < 0 || swapIndex >= newSections.length) return;

        [newSections[index], newSections[swapIndex]] = [newSections[swapIndex], newSections[index]];
        setSections(newSections);

        // Persist reorder
        const orderedIds = newSections.map((s) => s.id);
        router.post(
            route('landing-pages.sections.reorder', { landingPage: landingPageId }),
            { sections: orderedIds },
            { preserveScroll: true }
        );
    };

    const addSection = () => {
        setAdding(true);
        router.post(
            route('landing-pages.sections.store', { landingPage: landingPageId }),
            { type: addType, content: {}, is_visible: true },
            {
                preserveScroll: true,
                onFinish: () => setAdding(false),
                onSuccess: () => setAddType('hero'),
            }
        );
    };

    return (
        <div className="space-y-4">
            {/* Section rows */}
            <div className="space-y-2">
                {sections.length === 0 ? (
                    <p className="rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 py-10 text-center
                                  text-sm text-gray-400 dark:text-gray-500">
                        No sections yet. Add your first section below.
                    </p>
                ) : (
                    sections.map((section, idx) => (
                        <SectionRow
                            key={section.id}
                            section={section}
                            landingPageId={landingPageId}
                            isFirst={idx === 0}
                            isLast={idx === sections.length - 1}
                            onMoveUp={() => moveSection(idx, -1)}
                            onMoveDown={() => moveSection(idx, +1)}
                        />
                    ))
                )}
            </div>

            {/* Add section bar */}
            <div className="flex items-center gap-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600
                            bg-gray-50 dark:bg-gray-800/50 px-4 py-3">
                <select
                    value={addType}
                    onChange={(e) => setAddType(e.target.value)}
                    className="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                               text-sm text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-brand-500"
                >
                    {Object.entries(SECTION_LABELS).map(([key, label]) => (
                        <option key={key} value={key}>{SECTION_ICONS[key]} {label}</option>
                    ))}
                </select>
                <button
                    type="button"
                    onClick={addSection}
                    disabled={adding}
                    className="shrink-0 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium
                               text-white hover:bg-brand-600 disabled:opacity-60 transition-colors"
                >
                    {adding ? '…' : '+ Add Section'}
                </button>
            </div>
        </div>
    );
}
