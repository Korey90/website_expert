const LANGUAGE_LABELS = {
    en: 'English',
    pl: 'Polski',
    pt: 'Português',
};

export default function AiGeneratorForm({
    value,
    onChange,
    onGenerate,
    isGenerating,
    templates,
    conversionGoals,
    sectionTypes,
    errors,
    t,
}) {
    const includedSections = value.include_sections ?? [];

    const toggleSection = (sectionType) => {
        const nextSections = includedSections.includes(sectionType)
            ? includedSections.filter((current) => current !== sectionType)
            : [...includedSections, sectionType];

        onChange('include_sections', nextSections);
    };

    return (
        <section className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="mb-6">
                <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                    {t('ai.ui.input_block_title')}
                </p>
                <h3 className="mt-2 font-display text-2xl font-semibold text-neutral-900 dark:text-white">
                    {t('ai.ui.title')}
                </h3>
                <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    {t('ai.ui.input_block_description')}
                </p>
            </div>

            <div className="space-y-5">
                <div>
                    <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {t('ai.ui.goal')}
                    </label>
                    <select
                        value={value.goal}
                        onChange={(event) => onChange('goal', event.target.value)}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                    >
                        {Object.entries(conversionGoals ?? {}).map(([goal, label]) => (
                            <option key={goal} value={goal}>{label}</option>
                        ))}
                    </select>
                    {errors.goal && <p className="mt-2 text-sm text-red-500">{errors.goal[0]}</p>}
                </div>

                <div>
                    <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {t('ai.ui.campaign_name')}
                    </label>
                    <input
                        type="text"
                        value={value.campaign_name}
                        onChange={(event) => onChange('campaign_name', event.target.value)}
                        placeholder={t('ai.ui.campaign_name_placeholder')}
                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-neutral-500"
                    />
                    {errors.campaign_name && <p className="mt-2 text-sm text-red-500">{errors.campaign_name[0]}</p>}
                </div>

                <div>
                    <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {t('ai.ui.brief_label')}
                    </label>
                    <textarea
                        rows={5}
                        value={value.description}
                        onChange={(event) => onChange('description', event.target.value)}
                        placeholder={t('ai.ui.brief_placeholder')}
                        className="mt-2 w-full rounded-[1.5rem] border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-neutral-500"
                    />
                    {errors.description && <p className="mt-2 text-sm text-red-500">{errors.description[0]}</p>}
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                            {t('ai.ui.offer_summary')}
                        </label>
                        <textarea
                            rows={4}
                            value={value.offer_summary}
                            onChange={(event) => onChange('offer_summary', event.target.value)}
                            placeholder={t('ai.ui.offer_summary_placeholder')}
                            className="mt-2 w-full rounded-[1.5rem] border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-neutral-500"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                            {t('ai.ui.target_audience_override')}
                        </label>
                        <textarea
                            rows={4}
                            value={value.target_audience_override}
                            onChange={(event) => onChange('target_audience_override', event.target.value)}
                            placeholder={t('ai.ui.target_audience_placeholder')}
                            className="mt-2 w-full rounded-[1.5rem] border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white dark:placeholder:text-neutral-500"
                        />
                    </div>
                </div>

                <div className="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                            {t('ai.ui.language')}
                        </label>
                        <select
                            value={value.preferred_language}
                            onChange={(event) => onChange('preferred_language', event.target.value)}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            {Object.keys(LANGUAGE_LABELS).map((language) => (
                                <option key={language} value={language}>
                                    {LANGUAGE_LABELS[language]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                            {t('ai.ui.template')}
                        </label>
                        <select
                            value={value.template_key}
                            onChange={(event) => onChange('template_key', event.target.value)}
                            className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                        >
                            <option value="">{t('template_blank')}</option>
                            {Object.entries(templates ?? {}).map(([templateKey, template]) => (
                                <option key={templateKey} value={templateKey}>{template.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                        {t('ai.ui.sections')}
                    </label>
                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                        {Object.entries(sectionTypes ?? {}).map(([sectionType, definition]) => {
                            const isActive = includedSections.includes(sectionType);

                            return (
                                <button
                                    key={sectionType}
                                    type="button"
                                    onClick={() => toggleSection(sectionType)}
                                    className={[
                                        'rounded-2xl border px-4 py-3 text-left text-sm transition',
                                        isActive
                                            ? 'border-brand-400 bg-brand-50 text-brand-700 shadow-sm dark:border-brand-700 dark:bg-brand-900/20 dark:text-brand-300'
                                            : 'border-neutral-200 bg-neutral-50 text-neutral-600 hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                                    ].join(' ')}
                                >
                                    <span className="block font-semibold">{definition.label}</span>
                                    <span className="mt-1 block text-xs opacity-75">{sectionType}</span>
                                </button>
                            );
                        })}
                    </div>
                </div>

                <button
                    type="button"
                    onClick={onGenerate}
                    disabled={isGenerating}
                    className="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-neutral-950 px-5 py-4 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                >
                    {isGenerating ? t('ai.ui.generating') : t('ai.ui.generate')}
                    {!isGenerating && (
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    )}
                </button>
            </div>
        </section>
    );
}