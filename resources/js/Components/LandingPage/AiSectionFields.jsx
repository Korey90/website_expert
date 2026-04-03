import AiArrayItemsEditor from '@/Components/LandingPage/AiArrayItemsEditor';

const FORM_FIELDS = ['name', 'email', 'phone', 'message'];

function SettingSelect({ label, value, options, onChange }) {
    return (
        <label>
            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{label}</span>
            <select
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>{option.label}</option>
                ))}
            </select>
        </label>
    );
}

export default function AiSectionFields({ section, onChangeSection, t }) {
    const content = section.content ?? {};
    const settings = section.settings ?? {};

    const updateContent = (key, value) => {
        onChangeSection({
            ...section,
            content: { ...content, [key]: value },
        });
    };

    const updateSettings = (key, value) => {
        onChangeSection({
            ...section,
            settings: { ...settings, [key]: value },
        });
    };

    const toggleFormField = (fieldKey, targetKey) => {
        const values = Array.isArray(content[targetKey]) ? content[targetKey] : [];
        const nextValues = values.includes(fieldKey)
            ? values.filter((value) => value !== fieldKey)
            : [...values, fieldKey];

        updateContent(targetKey, nextValues);
    };

    return (
        <div className="space-y-5">
            <div className="grid gap-4 lg:grid-cols-3">
                <SettingSelect
                    label={t('ai.ui.background')}
                    value={settings.background ?? 'white'}
                    onChange={(value) => updateSettings('background', value)}
                    options={[
                        { value: 'white', label: 'White' },
                        { value: 'dark', label: 'Dark' },
                        { value: 'primary', label: 'Primary' },
                        { value: 'gradient', label: 'Gradient' },
                    ]}
                />
                <SettingSelect
                    label={t('ai.ui.padding')}
                    value={settings.padding ?? 'md'}
                    onChange={(value) => updateSettings('padding', value)}
                    options={[
                        { value: 'sm', label: 'Small' },
                        { value: 'md', label: 'Medium' },
                        { value: 'lg', label: 'Large' },
                    ]}
                />
                <label className="flex items-end">
                    <span className="flex w-full items-center justify-between rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100">
                        <span>{t('ai.ui.visible')}</span>
                        <input
                            type="checkbox"
                            checked={settings.visible !== false}
                            onChange={(event) => updateSettings('visible', event.target.checked)}
                            className="h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
                        />
                    </span>
                </label>
            </div>

            {(section.type === 'hero' || section.type === 'cta' || section.type === 'form' || section.type === 'text' || section.type === 'video' || section.type === 'faq' || section.type === 'features' || section.type === 'testimonials') && (
                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('headline')}</span>
                    <input
                        type="text"
                        value={content.headline ?? ''}
                        onChange={(event) => updateContent('headline', event.target.value)}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </label>
            )}

            {(section.type === 'hero' || section.type === 'cta' || section.type === 'form') && (
                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('subheadline')}</span>
                    <textarea
                        rows={3}
                        value={content.subheadline ?? ''}
                        onChange={(event) => updateContent('subheadline', event.target.value)}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </label>
            )}

            {(section.type === 'hero' || section.type === 'cta' || section.type === 'form') && (
                <div className="grid gap-4 lg:grid-cols-2">
                    <label>
                        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('cta_text')}</span>
                        <input
                            type="text"
                            value={content.cta_text ?? ''}
                            onChange={(event) => updateContent('cta_text', event.target.value)}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </label>

                    {section.type !== 'form' && (
                        <label>
                            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('cta_url')}</span>
                            <input
                                type="text"
                                value={content.cta_url ?? ''}
                                onChange={(event) => updateContent('cta_url', event.target.value)}
                                className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                        </label>
                    )}
                </div>
            )}

            {section.type === 'features' && (
                <AiArrayItemsEditor
                    items={content.items}
                    onChange={(items) => updateContent('items', items)}
                    t={t}
                    createItem={() => ({ icon: 'check', title: '', description: '' })}
                    fields={[
                        { key: 'icon', label: 'Icon' },
                        { key: 'title', label: 'Title' },
                        { key: 'description', label: 'Description', type: 'textarea', rows: 3, fullWidth: true },
                    ]}
                />
            )}

            {section.type === 'testimonials' && (
                <AiArrayItemsEditor
                    items={content.items}
                    onChange={(items) => updateContent('items', items)}
                    t={t}
                    createItem={() => ({ author: '', company: '', text: '', rating: 5 })}
                    fields={[
                        { key: 'author', label: 'Author' },
                        { key: 'company', label: 'Company' },
                        { key: 'rating', label: 'Rating', type: 'number', min: 1, max: 5 },
                        { key: 'text', label: 'Quote', type: 'textarea', rows: 4, fullWidth: true },
                    ]}
                />
            )}

            {section.type === 'faq' && (
                <AiArrayItemsEditor
                    items={content.items}
                    onChange={(items) => updateContent('items', items)}
                    t={t}
                    createItem={() => ({ question: '', answer: '' })}
                    fields={[
                        { key: 'question', label: 'Question', fullWidth: true },
                        { key: 'answer', label: 'Answer', type: 'textarea', rows: 4, fullWidth: true },
                    ]}
                />
            )}

            {section.type === 'form' && (
                <div className="space-y-4">
                    <label>
                        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">Success message</span>
                        <textarea
                            rows={3}
                            value={content.success_message ?? ''}
                            onChange={(event) => updateContent('success_message', event.target.value)}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        />
                    </label>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('ai.ui.fields')}</span>
                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {FORM_FIELDS.map((fieldKey) => (
                                    <label key={fieldKey} className="flex items-center gap-2 rounded-2xl border border-neutral-300 px-3 py-2.5 text-sm dark:border-neutral-700">
                                        <input
                                            type="checkbox"
                                            checked={(content.fields ?? []).includes(fieldKey)}
                                            onChange={() => toggleFormField(fieldKey, 'fields')}
                                            className="h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
                                        />
                                        <span className="capitalize">{fieldKey}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('ai.ui.required_fields')}</span>
                            <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                {FORM_FIELDS.map((fieldKey) => (
                                    <label key={fieldKey} className="flex items-center gap-2 rounded-2xl border border-neutral-300 px-3 py-2.5 text-sm dark:border-neutral-700">
                                        <input
                                            type="checkbox"
                                            checked={(content.required ?? []).includes(fieldKey)}
                                            onChange={() => toggleFormField(fieldKey, 'required')}
                                            className="h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500"
                                        />
                                        <span className="capitalize">{fieldKey}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {section.type === 'text' && (
                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">HTML</span>
                    <textarea
                        rows={7}
                        value={content.html ?? content.body ?? ''}
                        onChange={(event) => updateContent('html', event.target.value)}
                        className="mt-2 w-full rounded-[1.5rem] border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </label>
            )}

            {section.type === 'video' && (
                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('video_url')}</span>
                    <input
                        type="url"
                        value={content.video_url ?? content.url ?? ''}
                        onChange={(event) => updateContent('video_url', event.target.value)}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                </label>
            )}
        </div>
    );
}