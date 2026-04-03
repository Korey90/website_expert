import { Head } from '@inertiajs/react';

function resolveCanonicalUrl(slug) {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }

    return slug ? `/lp/${slug}` : '';
}

export default function LandingPageSeo({ landingPage = {} }) {
    const title = landingPage.meta_title || landingPage.title || '';
    const description = landingPage.meta_description || landingPage.description || '';
    const canonicalUrl = resolveCanonicalUrl(landingPage.slug);
    const image = landingPage.og_image_path || null;
    const locale = landingPage.language || 'en';

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: title,
        description,
        url: canonicalUrl,
        inLanguage: locale,
    };

    return (
        <Head>
            <title>{title}</title>
            {description && <meta name="description" content={description} />}
            <meta property="og:title" content={title} />
            {description && <meta property="og:description" content={description} />}
            <meta property="og:type" content="website" />
            {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}
            {image && <meta property="og:image" content={image} />}
            <meta name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
            <meta name="twitter:title" content={title} />
            {description && <meta name="twitter:description" content={description} />}
            {image && <meta name="twitter:image" content={image} />}
            <meta name="robots" content="index, follow" />
            {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}
            <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
        </Head>
    );
}