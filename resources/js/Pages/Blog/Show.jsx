import { Head, Link, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

const labels = {
    en: { home: 'Home', blog: 'Blog', related: 'Related articles', readMore: 'Read more →' },
    pl: { home: 'Strona główna', blog: 'Blog', related: 'Powiązane artykuły', readMore: 'Czytaj dalej →' },
    pt: { home: 'Início', blog: 'Blog', related: 'Artigos relacionados', readMore: 'Ler mais →' },
};

export default function BlogShow({ locale: localeProp, article, related, cta }) {
    useScrollReveal('.reveal');

    const { footer } = usePage().props;
    const locale = localeProp ?? 'en';
    const l = labels[locale] ?? labels.en;

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: article.title,
        description: article.excerpt,
        datePublished: article.published_at,
        inLanguage: locale,
    };

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>{article.meta_title || article.title}</title>
                <meta name="description" content={article.meta_description || article.excerpt || ''} />
                <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
            </Head>

            <article className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <nav className="mb-6 text-sm text-neutral-500 dark:text-neutral-400" aria-label="Breadcrumb">
                        <ol className="flex items-center gap-1.5 flex-wrap">
                            <li><Link href="/" className="hover:text-brand-500 transition-colors">{l.home}</Link></li>
                            <li aria-hidden="true">›</li>
                            <li><Link href="/blog" className="hover:text-brand-500 transition-colors">{l.blog}</Link></li>
                            <li aria-hidden="true">›</li>
                            <li className="text-neutral-700 dark:text-neutral-300" aria-current="page">{article.title}</li>
                        </ol>
                    </nav>

                    {article.category && (
                        <span className="inline-block mb-3 px-2.5 py-1 rounded-md text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 uppercase tracking-wide">
                            {article.category}
                        </span>
                    )}

                    <h1 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white mb-3">
                        {article.title}
                    </h1>

                    {article.published_at && (
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-8">{article.published_at}</p>
                    )}

                    <div
                        className="prose dark:prose-invert max-w-none prose-a:text-brand-500 prose-a:no-underline hover:prose-a:underline"
                        dangerouslySetInnerHTML={{ __html: article.content || '' }}
                    />

                    {cta?.url && (
                        <div className="mt-12 rounded-2xl bg-brand-500/10 border border-brand-500/30 p-6 text-center">
                            <Link href={cta.url} className="inline-flex items-center justify-center rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-6 py-3 text-sm transition-colors">
                                {cta.label}
                            </Link>
                        </div>
                    )}

                    {related?.length > 0 && (
                        <div className="mt-16 reveal">
                            <h2 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-6">{l.related}</h2>
                            <div className="grid sm:grid-cols-3 gap-6">
                                {related.map((item) => (
                                    <article key={item.slug} className="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5">
                                        <h3 className="font-bold text-sm text-neutral-900 dark:text-white mb-2">{item.title}</h3>
                                        <Link href={`/blog/${item.slug}`} className="text-brand-500 font-medium text-xs hover:underline">
                                            {l.readMore}
                                        </Link>
                                    </article>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </article>
        </MarketingLayout>
    );
}
