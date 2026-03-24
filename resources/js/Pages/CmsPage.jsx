import { useRef, useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

function slugify(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export default function CmsPage({ auth, page, navbar, footer }) {
    useScrollReveal('.reveal');

    const { locale = 'en', available_locales = {} } = usePage().props;
    const contentRef = useRef(null);
    const [toc, setToc]         = useState([]);
    const [activeId, setActiveId] = useState('');

    const metaTitle = page.meta_title || page.title;
    const metaDesc  = page.meta_description || '';
    const isLegal   = page.type && page.type !== 'page';

    /* ── Build TOC and inject IDs into <h2> elements after render ── */
    useEffect(() => {
        if (!isLegal || !contentRef.current) return;

        const headings = contentRef.current.querySelectorAll('h2');
        const items    = [];

        headings.forEach((h) => {
            if (!h.id) h.id = slugify(h.textContent);
            items.push({ id: h.id, text: h.textContent });
        });

        setToc(items);
    }, [page.content, isLegal]);

    /* ── IntersectionObserver for active TOC item ── */
    useEffect(() => {
        if (!isLegal || toc.length === 0 || !contentRef.current) return;

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) setActiveId(entry.target.id);
                }
            },
            { rootMargin: '-20% 0% -70% 0%', threshold: 0 },
        );

        contentRef.current.querySelectorAll('h2').forEach((h) => observer.observe(h));
        return () => observer.disconnect();
    }, [toc, isLegal]);

    const handlePrint = () => window.print();
    const handleTop   = () => window.scrollTo({ top: 0, behavior: 'smooth' });

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head title={`${String(metaTitle || 'WebsiteExpert')} – WebsiteExpert`}>
                <meta name="description" content={String(metaDesc || '')} />
            </Head>

            <main className="flex-1 pt-24 pb-20">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

                    {/* ── Simple page (non-legal) ── */}
                    {!isLegal && (
                        <div className="max-w-3xl mx-auto">
                            <h1 className="font-display text-3xl sm:text-4xl font-bold mb-8 text-neutral-900 dark:text-white">
                                {page.title}
                            </h1>
                            <div
                                className="prose prose-neutral dark:prose-invert max-w-none
                                           prose-headings:font-display
                                           prose-a:text-brand-500 prose-a:no-underline hover:prose-a:underline"
                                dangerouslySetInnerHTML={{ __html: page.content || '' }}
                            />
                        </div>
                    )}

                    {/* ── Legal document ── */}
                    {isLegal && (
                        <>
                            {/* Breadcrumb */}
                            <nav className="mb-6 text-sm text-neutral-500 dark:text-neutral-400" aria-label="Breadcrumb">
                                <ol className="flex items-center gap-1.5">
                                    <li>
                                        <a href="/" className="hover:text-brand-500 transition-colors">Home</a>
                                    </li>
                                    <li aria-hidden="true">›</li>
                                    <li className="text-neutral-700 dark:text-neutral-300" aria-current="page">Legal</li>
                                </ol>
                            </nav>

                            {/* Document header */}
                            <header className="mb-8 pb-6 border-b border-neutral-200 dark:border-neutral-700">
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <h1 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white">
                                        {page.title}
                                    </h1>

                                    {/* Print + Top buttons */}
                                    <div className="flex items-center gap-2 print:hidden">
                                        <button
                                            onClick={handlePrint}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-1.5 text-sm text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                                        >
                                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.75 19.5H17.25l-.468-5.671M13.5 18.75a.75.75 0 000 1.5h-3a.75.75 0 000-1.5h3z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 13.5V8.25A2.25 2.25 0 019 6h6a2.25 2.25 0 012.25 2.25V13.5" />
                                            </svg>
                                            Print
                                        </button>
                                        <button
                                            onClick={handleTop}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-1.5 text-sm text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                                        >
                                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5} aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                            Top
                                        </button>
                                    </div>
                                </div>

                                {/* Effective date / version / last updated */}
                                <dl className="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    {page.effective_date && (
                                        <div className="flex items-center gap-1.5">
                                            <dt>Effective:</dt>
                                            <dd className="text-neutral-700 dark:text-neutral-300">{page.effective_date}</dd>
                                        </div>
                                    )}
                                    {page.version && (
                                        <div className="flex items-center gap-1.5">
                                            <dt>Version:</dt>
                                            <dd className="text-neutral-700 dark:text-neutral-300">{page.version}</dd>
                                        </div>
                                    )}
                                    {page.updated_at && (
                                        <div className="flex items-center gap-1.5">
                                            <dt>Last updated:</dt>
                                            <dd className="text-neutral-700 dark:text-neutral-300">{page.updated_at}</dd>
                                        </div>
                                    )}
                                </dl>

                                {/* Language pills */}
                                {Object.keys(available_locales).length > 1 && (
                                    <div className="mt-4 flex items-center gap-2 print:hidden" aria-label="Language selector">
                                        <span className="text-xs text-neutral-400 uppercase tracking-wide">Language:</span>
                                        {Object.entries(available_locales).map(([code, label]) => (
                                            <a
                                                key={code}
                                                href={route('lang.switch', { locale: code })}
                                                aria-current={code === locale ? 'true' : undefined}
                                                className={[
                                                    'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition-colors',
                                                    code === locale
                                                        ? 'bg-brand-500 text-white'
                                                        : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-brand-50 dark:hover:bg-neutral-700',
                                                ].join(' ')}
                                            >
                                                {label}
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </header>

                            {/* Mobile: jump-to-section select */}
                            {toc.length > 0 && (
                                <div className="lg:hidden mb-6 print:hidden">
                                    <label htmlFor="toc-select" className="sr-only">Jump to section</label>
                                    <select
                                        id="toc-select"
                                        defaultValue=""
                                        onChange={(e) => {
                                            const el = document.getElementById(e.target.value);
                                            if (el) el.scrollIntoView({ behavior: 'smooth' });
                                        }}
                                        className="w-full rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-3 py-2 text-sm text-neutral-700 dark:text-neutral-300"
                                    >
                                        <option value="" disabled>Jump to section…</option>
                                        {toc.map((item) => (
                                            <option key={item.id} value={item.id}>{item.text}</option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            {/* Two-column layout: sticky TOC (desktop) + content */}
                            <div className={toc.length > 0 ? 'lg:grid lg:grid-cols-[240px_1fr] lg:gap-10' : ''}>

                                {/* Sticky TOC sidebar — desktop only */}
                                {toc.length > 0 && (
                                    <aside className="hidden lg:block print:hidden">
                                        <nav
                                            className="sticky top-28 max-h-[calc(100vh-8rem)] overflow-y-auto pr-2"
                                            aria-label="Table of contents"
                                        >
                                            <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
                                                Contents
                                            </p>
                                            <ol className="space-y-0.5">
                                                {toc.map((item) => (
                                                    <li key={item.id}>
                                                        <a
                                                            href={`#${item.id}`}
                                                            className={[
                                                                'block rounded px-2 py-1 text-sm transition-colors',
                                                                item.id === activeId
                                                                    ? 'text-brand-600 dark:text-brand-400 font-medium bg-brand-50 dark:bg-brand-900/20'
                                                                    : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white',
                                                            ].join(' ')}
                                                        >
                                                            {item.text}
                                                        </a>
                                                    </li>
                                                ))}
                                            </ol>
                                        </nav>
                                    </aside>
                                )}

                                {/* Document body */}
                                <div
                                    ref={contentRef}
                                    className="prose prose-neutral dark:prose-invert max-w-none
                                               prose-headings:font-display
                                               prose-a:text-brand-500 prose-a:no-underline hover:prose-a:underline
                                               prose-h2:scroll-mt-28 prose-h3:scroll-mt-28
                                               prose-h2:text-xl prose-h2:font-bold prose-h2:mt-10 prose-h2:mb-3 prose-h2:pb-3
                                               prose-h2:border-b prose-h2:border-neutral-200 dark:prose-h2:border-neutral-700
                                               prose-p:leading-relaxed prose-li:my-0.5 prose-ul:my-3 prose-ol:my-3"
                                    dangerouslySetInnerHTML={{ __html: page.content || '' }}
                                />
                            </div>
                        </>
                    )}

                </div>
            </main>
        </MarketingLayout>
    );
}
