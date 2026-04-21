import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function PortfolioShow({ locale: localeProp, project, auth }) {
    useScrollReveal('.reveal');

    const { footer } = usePage().props;
    const locale = localeProp ?? 'en';

    const t = (key) =>
        project?.[`${key}_${locale}`] ?? project?.[`${key}_en`] ?? '';

    const labels = {
        en: { back: '← Back to Portfolio', tags: 'Technologies', result: 'Result', client: 'Client' },
        pl: { back: '← Powrót do portfolio', tags: 'Technologie', result: 'Wynik', client: 'Klient' },
        pt: { back: '← Voltar ao Portfolio', tags: 'Tecnologias', result: 'Resultado', client: 'Cliente' },
    };
    const l = labels[locale] ?? labels.en;

    const title    = t('title');
    const tag      = t('tag');
    const desc     = t('desc');
    const result   = t('result');
    const tags     = Array.isArray(project.tags) ? project.tags : [];

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>{`${title} – Website Expert Portfolio`}</title>
                <meta name="description" content={desc} />
            </Head>

            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Back link */}
                    <a
                        href="/portfolio"
                        className="inline-flex items-center text-sm text-neutral-500 dark:text-neutral-400 hover:text-brand-500 mb-8 transition-colors"
                    >
                        {l.back}
                    </a>

                    {/* Hero image */}
                    {project.image && (
                        <div className="rounded-2xl overflow-hidden aspect-video mb-10 bg-neutral-100 dark:bg-neutral-800 reveal">
                            <img
                                src={project.image}
                                alt={title}
                                className="w-full h-full object-cover object-top"
                                loading="eager"
                                decoding="async"
                                fetchpriority="high"
                            />
                        </div>
                    )}

                    {/* Header */}
                    <div className="reveal">
                        {tag && (
                            <span className="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 dark:bg-brand-950/30 text-brand-600 dark:text-brand-400 mb-4">
                                {tag}
                            </span>
                        )}
                        <h1 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white mb-3 leading-tight">
                            {title}
                        </h1>
                        {project.client && (
                            <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-6">
                                <span className="font-semibold">{l.client}:</span> {project.client}
                            </p>
                        )}
                    </div>

                    {/* Description */}
                    <div className="prose prose-neutral dark:prose-invert max-w-none mb-8 reveal">
                        <p className="text-base text-neutral-700 dark:text-neutral-300 leading-relaxed">{desc}</p>
                    </div>

                    {/* Result callout */}
                    {result && (
                        <div className="rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900 px-6 py-4 mb-8 reveal">
                            <p className="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400 mb-1">{l.result}</p>
                            <p className="text-lg font-bold text-emerald-800 dark:text-emerald-300">{result}</p>
                        </div>
                    )}

                    {/* Tags */}
                    {tags.length > 0 && (
                        <div className="reveal">
                            <p className="text-xs font-semibold uppercase tracking-wide text-neutral-400 dark:text-neutral-500 mb-3">{l.tags}</p>
                            <div className="flex flex-wrap gap-2">
                                {tags.map((tag, i) => (
                                    <span key={i} className="text-sm px-3 py-1 rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300">
                                        {tag}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </section>
        </MarketingLayout>
    );
}
