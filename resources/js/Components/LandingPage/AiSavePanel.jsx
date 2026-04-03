function slugify(value) {
    return value
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function AiSavePanel({ value, onChange, onSave, isSaving, variant, templates, conversionGoals, errors, t }) {
    const handleMetaChange = (key, nextValue) => {
        onChange('meta', {
            ...(value.meta ?? {}),
            [key]: nextValue,
        });
    };

    return (
        <section className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="mb-6">
                <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                    {t('ai.ui.save_title')}
                </p>
                <h3 className="mt-2 font-display text-2xl font-semibold text-neutral-900 dark:text-white">
                    {t('ai.ui.save_title')}
                </h3>
                <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    {t('ai.ui.save_description')}
                </p>
            </div>

            <div className="space-y-4">
                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('field_title')}</span>
                    <input
                        type="text"
                        value={value.title ?? ''}
                        onChange={(event) => onChange('title', event.target.value)}
                        disabled={!variant}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                    {errors.title && <p className="mt-2 text-sm text-red-500">{errors.title[0]}</p>}
                </label>

                <label>
                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('field_slug')}</span>
                    <input
                        type="text"
                        value={value.slug ?? ''}
                        onChange={(event) => onChange('slug', slugify(event.target.value))}
                        disabled={!variant}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />
                    {errors.slug && <p className="mt-2 text-sm text-red-500">{errors.slug[0]}</p>}
                </label>

                <div className="grid gap-4 lg:grid-cols-2">
                    <label>
                        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('field_language')}</span>
                        <select
                            value={value.language ?? 'en'}
                            onChange={(event) => onChange('language', event.target.value)}
                            disabled={!variant}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <option value="en">English</option>
                            <option value="pl">Polski</option>
                            <option value="pt">Português</option>
                        </select>
                    </label>

                    <label>
                        <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('ai.ui.template')}</span>
                        <select
                            value={value.template_key ?? ''}
                            onChange={(event) => onChange('template_key', event.target.value)}
                            disabled={!variant}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <option value="">{t('template_blank')}</option>
                            {Object.entries(templates ?? {}).map(([templateKey, template]) => (
                                <option key={templateKey} value={templateKey}>{template.label}</option>
                            ))}
                        </select>
                    </label>
                </div>

                <div className="rounded-[1.5rem] border border-neutral-200 p-4 dark:border-neutral-800">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('ai.ui.meta_title')}</p>
                    <input
                        type="text"
                        value={value.meta?.meta_title ?? ''}
                        onChange={(event) => handleMetaChange('meta_title', event.target.value)}
                        disabled={!variant}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />

                    <p className="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('ai.ui.meta_description')}</p>
                    <textarea
                        rows={3}
                        value={value.meta?.meta_description ?? ''}
                        onChange={(event) => handleMetaChange('meta_description', event.target.value)}
                        disabled={!variant}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    />

                    <p className="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">{t('field_conversion_goal')}</p>
                    <select
                        value={value.meta?.conversion_goal ?? 'contact'}
                        onChange={(event) => handleMetaChange('conversion_goal', event.target.value)}
                        disabled={!variant}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        {Object.entries(conversionGoals ?? {}).map(([goal, label]) => (
                            <option key={goal} value={goal}>{label}</option>
                        ))}
                    </select>
                </div>

                <button
                    type="button"
                    onClick={onSave}
                    disabled={!variant || isSaving}
                    className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-5 py-4 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {isSaving ? t('ai.ui.saving') : t('ai.ui.save')}
                </button>
            </div>
        </section>
    );
}