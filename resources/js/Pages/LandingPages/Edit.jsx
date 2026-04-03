import { useState } from 'react';
import { Head, useForm, usePage, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/LandingPage/StatusBadge';
import SectionsList from '@/Components/LandingPage/SectionsList';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

const TABS = { settings: 'settings', sections: 'sections' };

export default function Edit({ page, sectionTypes = [], conversionGoals = [], languages = ['en', 'pl', 'pt'] }) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState(TABS.sections);
    const [publishing, setPublishing] = useState(false);

    const { data, setData, patch, processing, errors, isDirty } = useForm({
        title: page.title ?? '',
        slug: page.slug ?? '',
        language: page.language ?? 'en',
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
        conversion_goal: page.conversion_goal ?? '',
    });

    const handleSaveSettings = (e) => {
        e.preventDefault();
        patch(route('landing-pages.update', page.id), { preserveScroll: true });
    };

    const handlePublish = () => {
        setPublishing(true);
        router.post(route('landing-pages.publish', page.id), {}, {
            onFinish: () => setPublishing(false),
        });
    };

    const handleUnpublish = () => {
        setPublishing(true);
        router.post(route('landing-pages.unpublish', page.id), {}, {
            onFinish: () => setPublishing(false),
        });
    };

    const hasFormSection = (page.sections ?? []).some((s) => s.type === 'form');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3 flex-wrap">
                    <Link
                        href={route('landing-pages.index')}
                        className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                        aria-label="Back"
                    >
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </Link>
                    <h2 className="font-display text-xl font-semibold text-gray-900 dark:text-white truncate max-w-xs sm:max-w-sm">
                        {page.title}
                    </h2>
                    <StatusBadge status={page.status} />
                    <div className="ml-auto flex items-center gap-2">
                        {page.status === 'published' ? (
                            <>
                                <a
                                    href={page.public_url ?? route('lp.show', page.slug)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hidden sm:inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 hover:bg-green-100 transition"
                                >
                                    Live ↗
                                </a>
                                <button
                                    type="button"
                                    onClick={handleUnpublish}
                                    disabled={publishing}
                                    className="rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 font-semibold text-sm px-4 py-2 transition disabled:opacity-50"
                                >
                                    Unpublish
                                </button>
                            </>
                        ) : (
                            <button
                                type="button"
                                onClick={handlePublish}
                                disabled={publishing || !hasFormSection}
                                title={!hasFormSection ? 'Add a Form section first' : undefined}
                                className="rounded-xl bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-semibold text-sm px-4 py-2 transition"
                            >
                                {publishing ? 'Publishing…' : 'Publish'}
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Edit — ${page.title}`} />

            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-4">

                    {/* Flash */}
                    {flash?.success && (
                        <div className="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 px-4 py-3 text-sm">
                            {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 text-sm">
                            {flash.error}
                        </div>
                    )}

                    {/* No form section warning */}
                    {!hasFormSection && (
                        <div className="rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-300 px-4 py-3 text-sm flex items-start gap-2">
                            <svg className="h-4 w-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            This page has no Form section. Add one to capture leads and enable publishing.
                        </div>
                    )}

                    {/* Tabs */}
                    <div className="border-b border-gray-200 dark:border-gray-700">
                        <nav className="flex gap-1 -mb-px">
                            {[
                                { id: TABS.sections, label: 'Sections' },
                                { id: TABS.settings, label: 'Settings' },
                            ].map((tab) => (
                                <button
                                    key={tab.id}
                                    type="button"
                                    onClick={() => setActiveTab(tab.id)}
                                    className={`px-4 py-2.5 text-sm font-medium border-b-2 transition ${
                                        activeTab === tab.id
                                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </nav>
                    </div>

                    {/* Sections panel */}
                    {activeTab === TABS.sections && (
                        <SectionsList
                            sections={page.sections ?? []}
                            landingPageId={page.id}
                            sectionTypes={sectionTypes}
                        />
                    )}

                    {/* Settings panel */}
                    {activeTab === TABS.settings && (
                        <form onSubmit={handleSaveSettings} className="space-y-5">
                            <div className="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 space-y-5">
                                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">Page settings</h3>

                                <div>
                                    <InputLabel htmlFor="edit-title" value="Title *" />
                                    <TextInput
                                        id="edit-title"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                    />
                                    <InputError message={errors.title} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="edit-slug" value="Slug *" />
                                    <div className="mt-1 flex items-center rounded-xl border border-gray-300 dark:border-gray-600 overflow-hidden focus-within:ring-2 focus-within:ring-brand-500">
                                        <span className="bg-gray-50 dark:bg-gray-700 px-3 py-2.5 text-sm text-gray-500 dark:text-gray-400 border-r border-gray-300 dark:border-gray-600 select-none">
                                            /lp/
                                        </span>
                                        <input
                                            id="edit-slug"
                                            type="text"
                                            value={data.slug}
                                            onChange={(e) => setData('slug', e.target.value)}
                                            className="flex-1 bg-transparent px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none"
                                        />
                                    </div>
                                    <InputError message={errors.slug} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="edit-language" value="Language" />
                                    <select
                                        id="edit-language"
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
                                </div>
                            </div>

                            <div className="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-6 space-y-5">
                                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">SEO / Meta</h3>

                                <div>
                                    <InputLabel htmlFor="edit-meta-title" value="Meta title" />
                                    <TextInput
                                        id="edit-meta-title"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.meta_title}
                                        onChange={(e) => setData('meta_title', e.target.value)}
                                        placeholder="Defaults to page title"
                                    />
                                    <InputError message={errors.meta_title} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="edit-meta-desc" value="Meta description" />
                                    <textarea
                                        id="edit-meta-desc"
                                        rows={3}
                                        value={data.meta_description}
                                        onChange={(e) => setData('meta_description', e.target.value)}
                                        className="mt-1 block w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
                                        placeholder="Brief description for search engines (160 chars)"
                                        maxLength={160}
                                    />
                                    <div className="mt-1 text-right text-xs text-gray-400 dark:text-gray-500">
                                        {data.meta_description.length}/160
                                    </div>
                                    <InputError message={errors.meta_description} className="mt-1" />
                                </div>
                            </div>

                            <div className="flex items-center justify-between">
                                {isDirty && (
                                    <span className="text-xs text-amber-600 dark:text-amber-400">Unsaved changes</span>
                                )}
                                <PrimaryButton disabled={processing} className="ml-auto">
                                    {processing ? 'Saving…' : 'Save settings'}
                                </PrimaryButton>
                            </div>
                        </form>
                    )}

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
