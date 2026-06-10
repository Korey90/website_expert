import { Head, Link, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { useState } from 'react';
import * as HeroIconsOutline from '@heroicons/react/24/outline';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface BlockContent {
    [key: string]: unknown;
}

interface BlockSettings {
    bg?: 'white' | 'gray' | 'dark' | 'brand';
    columns?: '2' | '3' | '4';
    layout?: 'full' | 'split';
}

interface Block {
    id: number;
    type: string;
    content: BlockContent;
    settings: BlockSettings;
}

interface PageProps {
    page: {
        slug: string;
        title: string;
        meta_title: string | null;
        meta_description: string | null;
    };
    blocks: Block[];
    locale: string;
    auth: { user: { id: number; name: string } } | null;
    footer: { extra: Record<string, unknown> } | null;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const BG_CLASSES: Record<string, string> = {
    white: 'bg-white dark:bg-neutral-950',
    gray:  'bg-neutral-50 dark:bg-neutral-900',
    dark:  'bg-neutral-900 dark:bg-neutral-950',
    brand: 'bg-brand-500/5 dark:bg-brand-500/10',
};

function bg(settings: BlockSettings): string {
    return BG_CLASSES[settings.bg ?? 'white'] ?? BG_CLASSES.white;
}

function t(content: BlockContent, key: string, locale: string): string {
    const localeKey = `${key}_${locale}`;
    if (content[localeKey]) return String(content[localeKey]);
    if (content[`${key}_en`]) return String(content[`${key}_en`]);
    return '';
}

function colsClass(columns: string | undefined): string {
    return columns === '4' ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'
         : columns === '2' ? 'grid-cols-1 sm:grid-cols-2'
         : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
}

/** Filament Repeater stores items as {uuid: itemData} when saved without ->relationship().
 *  This helper converts that format (or a plain array) to a regular array. */
function toList(val: unknown): BlockContent[] {
    if (!val) return [];
    if (Array.isArray(val)) return val as BlockContent[];
    if (typeof val === 'object') return Object.values(val) as BlockContent[];
    return [];
}

/**
 * Render a Heroicon by name (outline variant, 24px).
 * Accepts names like "eye", "home", "heroicon-o-eye", "heroicon-o-home" etc.
 */
function HeroIcon({ name, className = 'w-5 h-5' }: { name: string; className?: string }) {
    const clean = name
        .replace(/^heroicon-[os]-/, '')
        .replace(/-./g, m => m[1].toUpperCase());
    const pascal = clean.charAt(0).toUpperCase() + clean.slice(1) + 'Icon';
    const IconComponent = (HeroIconsOutline as Record<string, React.ElementType>)[pascal];
    if (!IconComponent) {
        return (
            <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
        );
    }
    return <IconComponent className={className} />;
}

/** FileUpload stores either a plain string path or {uuid: path} object */
function resolveImagePath(raw: unknown): string | null {
    if (!raw) return null;
    if (typeof raw === 'string') return `/storage/${raw}`;
    if (typeof raw === 'object') {
        const values = Object.values(raw as Record<string, string>);
        return values.length > 0 ? `/storage/${values[0]}` : null;
    }
    return null;
}

// ---------------------------------------------------------------------------
// HeroBlock
// ---------------------------------------------------------------------------

function HeroBlock({ block, locale }: { block: Block; locale: string }) {
    const c = block.content;
    const badge      = t(c, 'badge', locale);
    const heading    = t(c, 'heading', locale);
    const subheading = t(c, 'subheading', locale);
    const ctaLabel   = t(c, 'cta_label', locale);
    const ctaUrl     = String(c.cta_url ?? '/contact');
    const imagePath  = resolveImagePath(c.image);

    return (
        <section className={`relative overflow-hidden border-b border-neutral-100 dark:border-neutral-800 pt-28 pb-20 ${bg(block.settings)}`}>
            {imagePath && (
                <div className="absolute inset-0 z-0">
                    <img src={imagePath} alt="" className="w-full h-full object-cover opacity-40 dark:opacity-20" />
                </div>
            )}
            <div className="relative z-10 mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {badge && (
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-brand-500/30 bg-brand-500/10 px-3 py-1 text-xs font-semibold text-brand-500 mb-5">
                        {badge}
                    </span>
                )}
                {heading && (
                    <h1 className="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-neutral-900 dark:text-white leading-tight mb-4">
                        {heading}
                    </h1>
                )}
                {subheading && (
                    <p className="text-lg text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto mb-8">
                        {subheading}
                    </p>
                )}
                {ctaLabel && (
                    <Link
                        href={ctaUrl}
                        className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                    >
                        {ctaLabel}
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                )}
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// FeaturesGridBlock
// ---------------------------------------------------------------------------

function FeaturesGridBlock({ block, locale }: { block: Block; locale: string }) {
    const c       = block.content;
    const items   = toList(c.items);
    const label   = t(c, 'section_label', locale);
    const heading = t(c, 'heading', locale);
    const sub     = t(c, 'subheading', locale);

    return (
        <section className={`py-20 md:py-28 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {(label || heading || sub) && (
                    <div className="text-center mb-12">
                        {label && (
                            <span className="inline-block text-xs font-semibold uppercase tracking-widest text-brand-500 mb-3">
                                {label}
                            </span>
                        )}
                        {heading && (
                            <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-3">
                                {heading}
                            </h2>
                        )}
                        {sub && (
                            <p className="text-neutral-500 dark:text-neutral-400 max-w-lg mx-auto">{sub}</p>
                        )}
                    </div>
                )}
                <div className={`grid gap-6 ${colsClass(block.settings.columns)}`}>
                    {items.map((item, i) => (
                        <div
                            key={i}
                            className="flex flex-col p-7 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-xl hover:shadow-brand-500/5 transition-all"
                        >
                            {item.icon && (
                                <div className="w-11 h-11 rounded-xl bg-brand-500/10 flex items-center justify-center mb-4 shrink-0">
                                    <HeroIcon name={String(item.icon)} className="w-5 h-5 text-brand-500" />
                                </div>
                            )}
                            <h3 className="font-semibold text-neutral-900 dark:text-white mb-2">
                                {t(item, 'title', locale)}
                            </h3>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed">
                                {t(item, 'desc', locale)}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// PackagesBlock
// ---------------------------------------------------------------------------

function PackagesBlock({ block, locale }: { block: Block; locale: string }) {
    const c        = block.content;
    const packages = toList(c.packages);
    const heading  = t(c, 'heading', locale);
    const sub      = t(c, 'subheading', locale);

    return (
        <section id="packages" className={`py-20 md:py-28 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {(heading || sub) && (
                    <div className="text-center mb-12">
                        {heading && (
                            <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-3">
                                {heading}
                            </h2>
                        )}
                        {sub && (
                            <p className="text-neutral-500 dark:text-neutral-400 max-w-xl mx-auto">{sub}</p>
                        )}
                    </div>
                )}
                <div className={`grid gap-8 ${colsClass(String(packages.length))}`}>
                    {packages.map((pkg, i) => {
                        const highlight   = Boolean(pkg.highlight);
                        const badge       = t(pkg, 'badge', locale);
                        const name        = t(pkg, 'name', locale);
                        const desc        = t(pkg, 'desc', locale);
                        const price       = String(pkg.price ?? '');
                        const featuresRaw = t(pkg, 'features', locale);
                        const features    = featuresRaw ? featuresRaw.split('\n').filter(Boolean) : [];
                        const ctaLabel    = t(pkg, 'cta_label', locale) || 'Get Started';
                        const ctaUrl      = String(pkg.cta_url ?? '/contact');

                        return (
                            <div
                                key={i}
                                className={`relative flex flex-col rounded-2xl border p-8 ${
                                    highlight
                                        ? 'border-brand-500 bg-brand-500/5 dark:bg-brand-500/10 shadow-md shadow-brand-500/10'
                                        : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900'
                                }`}
                            >
                                {badge && (
                                    <span className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold bg-brand-500 text-white shadow-sm">
                                        {badge}
                                    </span>
                                )}
                                {price && (
                                    <p className="text-xs font-semibold text-brand-500 uppercase tracking-widest mb-1">{price}</p>
                                )}
                                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-2">{name}</h3>
                                {desc && (
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-6">{desc}</p>
                                )}
                                {features.length > 0 && (
                                    <ul className="space-y-2 mb-8 flex-1">
                                        {features.map((f, fi) => (
                                            <li key={fi} className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                                <svg className="w-4 h-4 text-brand-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                {f}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <Link
                                    href={ctaUrl}
                                    className={`mt-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm transition-all ${
                                        highlight
                                            ? 'bg-brand-500 text-white hover:bg-brand-600 shadow-lg shadow-brand-500/20'
                                            : 'border border-brand-500 text-brand-500 hover:bg-brand-500 hover:text-white'
                                    }`}
                                >
                                    {ctaLabel}
                                </Link>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// PricingTableBlock
// ---------------------------------------------------------------------------

function PricingTableBlock({ block, locale }: { block: Block; locale: string }) {
    const c       = block.content;
    const rows    = toList(c.rows);
    const heading = t(c, 'heading', locale);

    return (
        <section className={`py-20 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                {heading && (
                    <h2 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-8 text-center">
                        {heading}
                    </h2>
                )}
                <div className="rounded-2xl border border-neutral-200 dark:border-neutral-800 overflow-hidden">
                    <table className="w-full text-sm">
                        <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                            {rows.map((row, i) => (
                                <tr key={i} className={i % 2 === 0 ? 'bg-white dark:bg-neutral-900' : 'bg-neutral-50 dark:bg-neutral-900/50'}>
                                    <td className="px-5 py-3 text-neutral-700 dark:text-neutral-300">
                                        {t(row, 'label', locale)}
                                        {t(row, 'note', locale) && (
                                            <span className="ml-2 text-xs text-neutral-400">{t(row, 'note', locale)}</span>
                                        )}
                                    </td>
                                    <td className="px-5 py-3 text-right font-bold text-neutral-900 dark:text-white">
                                        {String(row.price ?? '—')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// FaqBlock
// ---------------------------------------------------------------------------

function FaqBlock({ block, locale }: { block: Block; locale: string }) {
    const c       = block.content;
    const items   = toList(c.items);
    const heading = t(c, 'heading', locale);
    const [open, setOpen] = useState<number | null>(null);

    return (
        <section className={`py-20 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                {heading && (
                    <h2 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-8 text-center">
                        {heading}
                    </h2>
                )}
                <div className="space-y-3">
                    {items.map((item, i) => (
                        <div
                            key={i}
                            className="rounded-xl border border-neutral-200 dark:border-neutral-800 overflow-hidden"
                        >
                            <button
                                type="button"
                                onClick={() => setOpen(open === i ? null : i)}
                                className="w-full flex items-center justify-between px-5 py-4 text-left bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors"
                            >
                                <span className="font-medium text-neutral-900 dark:text-white text-sm">
                                    {t(item, 'q', locale)}
                                </span>
                                <svg
                                    className={`w-4 h-4 text-neutral-400 shrink-0 transition-transform ${open === i ? 'rotate-180' : ''}`}
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            {open === i && (
                                <div className="px-5 py-4 text-sm text-neutral-600 dark:text-neutral-400 bg-neutral-50 dark:bg-neutral-800/30 border-t border-neutral-100 dark:border-neutral-800">
                                    {t(item, 'a', locale)}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// CtaBannerBlock
// ---------------------------------------------------------------------------

function CtaBannerBlock({ block, locale }: { block: Block; locale: string }) {
    const c          = block.content;
    const heading    = t(c, 'heading', locale);
    const subheading = t(c, 'subheading', locale);
    const ctaLabel   = t(c, 'cta_label', locale);
    const ctaUrl     = String(c.cta_url ?? '/contact');

    return (
        <section className={`py-16 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {heading && (
                    <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-3">
                        {heading}
                    </h2>
                )}
                {subheading && (
                    <p className="text-neutral-500 dark:text-neutral-400 mb-8 max-w-xl mx-auto">{subheading}</p>
                )}
                {ctaLabel && (
                    <Link
                        href={ctaUrl}
                        className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                    >
                        {ctaLabel}
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                )}
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// TextSectionBlock
// ---------------------------------------------------------------------------

function TextSectionBlock({ block, locale }: { block: Block; locale: string }) {
    const c       = block.content;
    const heading = t(c, 'heading', locale);
    const body    = t(c, 'body', locale);
    const isSplit = block.settings.layout === 'split';

    return (
        <section className={`py-20 ${bg(block.settings)}`}>
            <div className={`mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 ${isSplit ? 'grid gap-12 lg:grid-cols-2 items-start' : 'max-w-3xl'}`}>
                {heading && (
                    <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white">
                        {heading}
                    </h2>
                )}
                {body && (
                    <div
                        className="prose prose-neutral dark:prose-invert max-w-none text-sm leading-relaxed"
                        dangerouslySetInnerHTML={{ __html: body }}
                    />
                )}
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// ComparisonTableBlock
// ---------------------------------------------------------------------------

function ComparisonTableBlock({ block, locale }: { block: Block; locale: string }) {
    const c       = block.content;
    const cols    = toList(c.columns);
    const rows    = toList(c.rows);
    const heading = t(c, 'heading', locale);

    function renderValue(raw: unknown): React.ReactNode {
        if (raw === true  || raw === 'true')  return <svg className="w-4 h-4 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>;
        if (raw === false || raw === 'false') return <svg className="w-4 h-4 text-neutral-300 dark:text-neutral-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>;
        return <span className="text-xs text-neutral-600 dark:text-neutral-400">{String(raw ?? '—')}</span>;
    }

    return (
        <section className={`py-20 ${bg(block.settings)}`}>
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 overflow-x-auto">
                {heading && (
                    <h2 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-8 text-center">
                        {heading}
                    </h2>
                )}
                <table className="w-full text-sm rounded-2xl overflow-hidden border border-neutral-200 dark:border-neutral-800">
                    <thead>
                        <tr className="bg-neutral-50 dark:bg-neutral-900">
                            <th className="px-5 py-3 text-left text-xs font-medium text-neutral-500 uppercase" />
                            {cols.map((col, i) => (
                                <th
                                    key={i}
                                    className={`px-5 py-3 text-center text-xs font-bold uppercase ${
                                        col.highlight ? 'text-brand-500' : 'text-neutral-700 dark:text-neutral-300'
                                    }`}
                                >
                                    {t(col, 'label', locale)}
                                    {col.price && (
                                        <div className="text-sm font-bold text-neutral-900 dark:text-white mt-0.5">
                                            {String(col.price)}
                                        </div>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                        {rows.map((row, ri) => {
                            let values: unknown[] = [];
                            try {
                                const raw = row.values;
                                values = typeof raw === 'string' ? JSON.parse(raw) : (Array.isArray(raw) ? raw : []);
                            } catch { values = []; }

                            return (
                                <tr key={ri} className={ri % 2 === 0 ? 'bg-white dark:bg-neutral-950' : 'bg-neutral-50 dark:bg-neutral-900/50'}>
                                    <td className="px-5 py-3 font-medium text-neutral-700 dark:text-neutral-300">
                                        {t(row, 'label', locale)}
                                    </td>
                                    {cols.map((_, ci) => (
                                        <td key={ci} className="px-5 py-3 text-center">
                                            {renderValue(values[ci])}
                                        </td>
                                    ))}
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </section>
    );
}

// ---------------------------------------------------------------------------
// Block Dispatcher
// ---------------------------------------------------------------------------

function BlockRenderer({ block, locale }: { block: Block; locale: string }) {
    switch (block.type) {
        case 'hero':             return <HeroBlock block={block} locale={locale} />;
        case 'features_grid':    return <FeaturesGridBlock block={block} locale={locale} />;
        case 'packages':         return <PackagesBlock block={block} locale={locale} />;
        case 'pricing_table':    return <PricingTableBlock block={block} locale={locale} />;
        case 'faq':              return <FaqBlock block={block} locale={locale} />;
        case 'cta_banner':       return <CtaBannerBlock block={block} locale={locale} />;
        case 'text_section':     return <TextSectionBlock block={block} locale={locale} />;
        case 'comparison_table': return <ComparisonTableBlock block={block} locale={locale} />;
        default:                 return null;
    }
}

// ---------------------------------------------------------------------------
// Page
// ---------------------------------------------------------------------------

export default function ServicePage({ page, blocks, locale, auth, footer }: PageProps) {
    const { locale: pageLocale } = usePage<{ locale: string }>().props;
    const resolvedLocale = locale ?? pageLocale ?? 'en';

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>{page.meta_title ?? page.title}</title>
                {page.meta_description && (
                    <meta name="description" content={page.meta_description} />
                )}
            </Head>

            {blocks.map((block) => (
                <BlockRenderer key={block.id} block={block} locale={resolvedLocale} />
            ))}

            {blocks.length === 0 && (
                <div className="min-h-screen flex items-center justify-center text-neutral-400 dark:text-neutral-600 text-sm">
                    Page is being built…
                </div>
            )}
        </MarketingLayout>
    );
}
