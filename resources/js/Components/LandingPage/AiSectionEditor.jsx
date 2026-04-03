import { useState } from 'react';
import AiSectionFields from '@/Components/LandingPage/AiSectionFields';

export default function AiSectionEditor({ sections, onUpdateSection, onRegenerateSection, regeneratingSections, t }) {
    const [instructions, setInstructions] = useState({});

    return (
        <section className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="mb-6 flex items-end justify-between gap-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                        {t('ai.ui.editor_title')}
                    </p>
                    <h3 className="mt-2 font-display text-2xl font-semibold text-neutral-900 dark:text-white">
                        {t('ai.ui.editor_title')}
                    </h3>
                    <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        {t('ai.ui.editor_description')}
                    </p>
                </div>
                <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300">
                    {t('ai.ui.status_ready')}
                </span>
            </div>

            <div className="space-y-4">
                {(sections ?? []).map((section, index) => {
                    const instructionKey = `${section.type}-${index}`;
                    const sectionLabel = t(`section_${section.type}`);
                    const isRegenerating = Boolean(regeneratingSections?.[section.type]);

                    return (
                        <article key={instructionKey} className="rounded-[1.75rem] border border-neutral-200 bg-neutral-50 p-5 dark:border-neutral-800 dark:bg-neutral-950/60">
                            <div className="mb-5 flex items-center justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                                        Section {index + 1}
                                    </p>
                                    <h4 className="mt-1 text-lg font-semibold text-neutral-900 dark:text-white">
                                        {sectionLabel}
                                    </h4>
                                </div>
                                <span className="rounded-full border border-neutral-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                    {section.type}
                                </span>
                            </div>

                            <AiSectionFields
                                section={section}
                                onChangeSection={(nextSection) => onUpdateSection(index, nextSection)}
                                t={t}
                            />

                            <div className="mt-5 rounded-[1.5rem] border border-dashed border-neutral-300 p-4 dark:border-neutral-700">
                                <label>
                                    <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">
                                        {t('ai.ui.regenerate_instruction')}
                                    </span>
                                    <textarea
                                        rows={3}
                                        value={instructions[instructionKey] ?? ''}
                                        onChange={(event) => setInstructions((current) => ({
                                            ...current,
                                            [instructionKey]: event.target.value,
                                        }))}
                                        placeholder={t('ai.ui.regenerate_instruction_placeholder')}
                                        className="mt-2 w-full rounded-2xl border border-neutral-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                    />
                                </label>

                                <button
                                    type="button"
                                    onClick={() => onRegenerateSection(section.type, instructions[instructionKey] ?? '')}
                                    disabled={isRegenerating}
                                    className="mt-3 inline-flex items-center gap-2 rounded-full border border-neutral-300 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-700 transition hover:border-brand-400 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-60 dark:border-neutral-700 dark:text-neutral-200 dark:hover:border-brand-700 dark:hover:text-brand-300"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865A8.25 8.25 0 0117.803 6.3l3.181 3.182" />
                                    </svg>
                                    {isRegenerating ? t('ai.ui.regenerating') : t('ai.ui.regenerate')}
                                </button>
                            </div>
                        </article>
                    );
                })}
            </div>
        </section>
    );
}