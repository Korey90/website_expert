import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import PortalLayout from '@/Layouts/PortalLayout';
import AiGeneratorForm from '@/Components/LandingPage/AiGeneratorForm';
import AiLandingPreview from '@/Components/LandingPage/AiLandingPreview';
import AiProfileSnapshot from '@/Components/LandingPage/AiProfileSnapshot';
import AiSavePanel from '@/Components/LandingPage/AiSavePanel';
import AiSectionEditor from '@/Components/LandingPage/AiSectionEditor';
import useAiLandingGenerator from '@/Hooks/useAiLandingGenerator';
import useLandingPageTrans from '@/Hooks/useLandingPageTrans';

function buildInitialGenerateForm(conversionGoals, business, sectionTypes) {
    return {
        goal: Object.keys(conversionGoals ?? {})[0] ?? 'contact',
        description: '',
        campaign_name: '',
        target_audience_override: '',
        offer_summary: '',
        preferred_language: business?.locale ?? 'en',
        template_key: '',
        include_sections: Object.keys(sectionTypes ?? {}).filter((sectionType) => ['hero', 'features', 'cta', 'form'].includes(sectionType)),
    };
}

function buildSaveForm(variant) {
    return {
        title: variant?.title ?? '',
        slug: variant?.slug_suggestion ?? '',
        language: variant?.language ?? 'en',
        template_key: variant?.template_key ?? '',
        meta: {
            meta_title: variant?.meta?.meta_title ?? '',
            meta_description: variant?.meta?.meta_description ?? '',
            conversion_goal: variant?.meta?.conversion_goal ?? 'contact',
        },
    };
}

export default function AiGenerator({ business, profile, profileCompletion, templates, conversionGoals, sectionTypes, client }) {
    const { flash } = usePage().props;
    const t = useLandingPageTrans();
    const [generateForm, setGenerateForm] = useState(() => buildInitialGenerateForm(conversionGoals, business, sectionTypes));
    const [saveForm, setSaveForm] = useState(() => buildSaveForm(null));
    const {
        variant,
        errors,
        notice,
        isGenerating,
        isSaving,
        regeneratingSections,
        setNotice,
        generate,
        regenerateSection,
        save,
        updateSection,
    } = useAiLandingGenerator();

    useEffect(() => {
        if (variant?.id) {
            setSaveForm(buildSaveForm(variant));
        }
    }, [variant?.id]);

    const handleGenerateChange = (key, value) => {
        setGenerateForm((current) => ({ ...current, [key]: value }));
    };

    const handleSaveChange = (key, value) => {
        setSaveForm((current) => ({ ...current, [key]: value }));
    };

    const handleGenerate = async () => {
        await generate(generateForm, t('ai.errors.generation_failed'));
    };

    const handleRegenerateSection = async (sectionType, instruction) => {
        await regenerateSection(sectionType, instruction, t('ai.errors.section_regeneration_failed'));
    };

    const handleSave = async () => {
        if (!variant) {
            return;
        }

        await save({
            ...saveForm,
            sections: variant.sections,
        }, t('ai.errors.generation_failed'));
    };

    const previewVariant = variant ? {
        ...variant,
        title: saveForm.title || variant.title,
        language: saveForm.language || variant.language,
        template_key: saveForm.template_key || variant.template_key,
        slug_suggestion: saveForm.slug || variant.slug_suggestion,
        meta: {
            ...(variant.meta ?? {}),
            ...(saveForm.meta ?? {}),
        },
    } : null;

    return (
        <PortalLayout client={client}>
            <Head title={t('ai.ui.eyebrow')} />

            <div className="mb-8 flex flex-wrap items-center gap-4 justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                        {t('ai.ui.eyebrow')}
                    </p>
                    <h1 className="mt-2 font-display text-3xl font-semibold text-neutral-900 dark:text-white">
                        {t('ai.ui.title')}
                    </h1>
                    <p className="mt-2 max-w-3xl text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                        {t('ai.ui.description')}
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    <Link
                        href={route('landing-pages.create')}
                        className="inline-flex items-center gap-2 rounded-2xl border border-neutral-300 px-4 py-3 text-sm font-semibold text-neutral-700 transition hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-200 dark:hover:bg-neutral-800"
                    >
                        {t('ai.ui.manual_builder')}
                    </Link>
                    <Link
                        href={route('landing-pages.index')}
                        className="inline-flex items-center gap-2 rounded-2xl bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-black dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200"
                    >
                        {t('back_to_pages')}
                    </Link>
                </div>
            </div>

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {(flash?.success || notice) && (
                        <div className={[
                            'mb-6 rounded-2xl border px-5 py-4 text-sm shadow-sm',
                            (notice?.type === 'error')
                                ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-900/20 dark:text-red-300'
                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-900/20 dark:text-emerald-300',
                        ].join(' ')}>
                            {notice?.text ?? flash?.success}
                        </div>
                    )}

                    <div className="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
                        <div className="space-y-6 xl:sticky xl:top-24 xl:self-start">
                            <AiProfileSnapshot
                                business={business}
                                profile={profile}
                                completion={profileCompletion}
                                t={t}
                            />

                            <AiGeneratorForm
                                value={generateForm}
                                onChange={handleGenerateChange}
                                onGenerate={handleGenerate}
                                isGenerating={isGenerating}
                                templates={templates}
                                conversionGoals={conversionGoals}
                                sectionTypes={sectionTypes}
                                errors={errors}
                                t={t}
                            />

                            <AiSavePanel
                                value={saveForm}
                                onChange={handleSaveChange}
                                onSave={handleSave}
                                isSaving={isSaving}
                                variant={variant}
                                templates={templates}
                                conversionGoals={conversionGoals}
                                errors={errors}
                                t={t}
                            />
                        </div>

                        <div className="space-y-6">
                            <AiLandingPreview variant={previewVariant} t={t} />

                            {variant && (
                                <AiSectionEditor
                                    sections={variant.sections}
                                    onUpdateSection={updateSection}
                                    onRegenerateSection={handleRegenerateSection}
                                    regeneratingSections={regeneratingSections}
                                    t={t}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </PortalLayout>
    );
}