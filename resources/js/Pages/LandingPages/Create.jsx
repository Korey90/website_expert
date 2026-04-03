import { useState } from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import TemplateCard from '@/Components/LandingPage/TemplateCard';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

const STEPS = { template: 1, details: 2 };

function slugify(str) {
    return str
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function Create({ templates = [], languages = ['en', 'pl', 'pt'] }) {
    const { flash } = usePage().props;
    const [step, setStep] = useState(STEPS.template);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        slug: '',
        language: 'en',
        template_key: '',
    });

    const handleTemplateSelect = (key) => {
        setData('template_key', key);
    };

    const handleTitleChange = (e) => {
        const title = e.target.value;
        setData((prev) => ({
            ...prev,
            title,
            slug: prev.slug === '' || prev.slug === slugify(prev.title) ? slugify(title) : prev.slug,
        }));
    };

    const handleNext = () => {
        if (!data.template_key) return;
        setStep(STEPS.details);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('landing-pages.store'));
    };

    const selectedTemplate = templates.find((t) => t.key === data.template_key);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => step === STEPS.details ? setStep(STEPS.template) : router.visit(route('landing-pages.index'))}
                        className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                        aria-label="Back"
                    >
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                    <h2 className="font-display text-xl font-semibold text-gray-900 dark:text-white">
                        {step === STEPS.template ? 'Choose a template' : 'Page details'}
                    </h2>
                    <span className="ml-auto text-xs text-gray-400 dark:text-gray-500">
                        Step {step} of 2
                    </span>
                </div>
            }
        >
            <Head title="New Landing Page" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">

                    <div className="overflow-hidden rounded-3xl border border-brand-200/70 bg-gradient-to-br from-brand-50 via-white to-amber-50 shadow-sm dark:border-brand-900/60 dark:from-neutral-900 dark:via-neutral-900 dark:to-brand-950/40">
                        <div className="grid gap-6 px-6 py-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
                            <div className="space-y-3">
                                <span className="inline-flex rounded-full border border-brand-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-brand-700 dark:border-brand-800 dark:bg-brand-900/30 dark:text-brand-300">
                                    AI Landing Page Generator
                                </span>
                                <h3 className="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                    Start from your Business Profile instead of a blank template.
                                </h3>
                                <p className="max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    Generate a first draft with hero, benefits, CTA and lead form, preview it instantly, then edit each section before saving.
                                </p>
                            </div>
                            <div className="flex items-center justify-start lg:justify-end">
                                <Link
                                    href={route('landing-pages.ai.create')}
                                    className="inline-flex items-center gap-2 rounded-2xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-black dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                                >
                                    Generate with AI
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {flash?.error && (
                        <div className="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 text-sm">
                            {flash.error}
                        </div>
                    )}

                    {/* Step 1: Template selection */}
                    {step === STEPS.template && (
                        <div className="space-y-6">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Select a starting template. You can customize every section afterwards.
                            </p>

                            {templates.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {templates.map((tpl) => (
                                        <TemplateCard
                                            key={tpl.key}
                                            template={tpl}
                                            selected={data.template_key === tpl.key}
                                            onSelect={handleTemplateSelect}
                                        />
                                    ))}
                                    {/* Blank option */}
                                    <TemplateCard
                                        template={{
                                            key: 'blank',
                                            label: 'Blank page',
                                            description: 'Start from scratch and add sections manually.',
                                            icon: '📄',
                                            sections: [],
                                        }}
                                        selected={data.template_key === 'blank'}
                                        onSelect={handleTemplateSelect}
                                    />
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {[
                                        { key: 'lead_magnet', label: 'Lead Magnet', description: 'Capture emails with a freebie or offer.', icon: '🎯', sections: ['hero', 'features', 'form'] },
                                        { key: 'services', label: 'Services', description: 'Showcase what you offer and convert visitors.', icon: '💼', sections: ['hero', 'features', 'testimonials', 'cta', 'form'] },
                                        { key: 'blank', label: 'Blank page', description: 'Start from scratch.', icon: '📄', sections: [] },
                                    ].map((tpl) => (
                                        <TemplateCard
                                            key={tpl.key}
                                            template={tpl}
                                            selected={data.template_key === tpl.key}
                                            onSelect={handleTemplateSelect}
                                        />
                                    ))}
                                </div>
                            )}

                            {errors.template_key && (
                                <InputError message={errors.template_key} />
                            )}

                            <div className="flex justify-end">
                                <PrimaryButton
                                    type="button"
                                    disabled={!data.template_key}
                                    onClick={handleNext}
                                >
                                    Continue →
                                </PrimaryButton>
                            </div>
                        </div>
                    )}

                    {/* Step 2: Details */}
                    {step === STEPS.details && (
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {selectedTemplate && (
                                <div className="flex items-center gap-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 px-4 py-3">
                                    <span className="text-2xl">{selectedTemplate.icon}</span>
                                    <div>
                                        <div className="text-sm font-semibold text-brand-800 dark:text-brand-300">{selectedTemplate.label}</div>
                                        <div className="text-xs text-brand-600 dark:text-brand-400">{selectedTemplate.description}</div>
                                    </div>
                                </div>
                            )}

                            <div className="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 space-y-5">
                                <div>
                                    <InputLabel htmlFor="lp-title" value="Page title *" />
                                    <TextInput
                                        id="lp-title"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.title}
                                        onChange={handleTitleChange}
                                        placeholder="e.g. Free SEO Audit"
                                        required
                                    />
                                    <InputError message={errors.title} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="lp-slug" value="URL slug *" />
                                    <div className="mt-1 flex items-center rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden focus-within:ring-2 focus-within:ring-brand-500">
                                        <span className="bg-gray-50 dark:bg-gray-700 px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 border-r border-gray-300 dark:border-gray-600 select-none">
                                            /lp/
                                        </span>
                                        <input
                                            id="lp-slug"
                                            type="text"
                                            value={data.slug}
                                            onChange={(e) => setData('slug', slugify(e.target.value))}
                                            className="flex-1 bg-transparent px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none"
                                            placeholder="free-seo-audit"
                                            required
                                        />
                                    </div>
                                    <InputError message={errors.slug} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="lp-language" value="Language" />
                                    <select
                                        id="lp-language"
                                        value={data.language}
                                        onChange={(e) => setData('language', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                                    >
                                        {languages.map((lang) => (
                                            <option key={lang} value={lang}>
                                                {lang === 'en' ? 'English' : lang === 'pl' ? 'Polish' : lang === 'pt' ? 'Portuguese' : lang}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.language} className="mt-1" />
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                <button
                                    type="button"
                                    onClick={() => setStep(STEPS.template)}
                                    className="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                                >
                                    ← Back to templates
                                </button>
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Creating…' : 'Create page'}
                                </PrimaryButton>
                            </div>
                        </form>
                    )}

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
