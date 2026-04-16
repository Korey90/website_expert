import { lazy, Suspense } from 'react';
import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import Hero           from '@/Components/Marketing/Hero';
import About          from '@/Components/Marketing/About';
import useScrollReveal from '@/Hooks/useScrollReveal';

// Generyczny renderer dla sekcji bez dedykowanego komponentu
function GenericSection({ data }) {
    // Pola title/subtitle/body/button_text są już rozwiązane do bieżącego locale
    // przez Spatie HasTranslations po stronie PHP — przychodza jako plain string
    const title    = typeof data.title    === 'string' ? data.title    : (data.title?.en    ?? '');
    const subtitle = typeof data.subtitle === 'string' ? data.subtitle : (data.subtitle?.en ?? '');
    const body     = typeof data.body     === 'string' ? data.body     : (data.body?.en     ?? '');
    const btnText  = typeof data.button_text === 'string' ? data.button_text : (data.button_text?.en ?? '');
    const btnUrl   = data.button_url;

    if (!title && !subtitle && !body) return null;

    return (
        <section id={data.key} className="py-16 md:py-24 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                {title    && <h2 className="font-display text-3xl font-black tracking-tight text-neutral-900 dark:text-white sm:text-4xl">{title}</h2>}
                {subtitle && <p className="mt-4 text-lg text-neutral-600 dark:text-white/72">{subtitle}</p>}
                {body     && <div className="mt-6 text-neutral-700 dark:text-white/80" dangerouslySetInnerHTML={{ __html: body }} />}
                {btnText && btnUrl && (
                    <a href={btnUrl} className="mt-8 inline-flex items-center justify-center rounded-2xl bg-brand-500 px-7 py-4 text-sm font-bold text-white hover:bg-brand-600">
                        {btnText}
                    </a>
                )}
            </div>
        </section>
    );
}

// Komponenty below-fold — ładowane leniwie
const SaasLandingSection = lazy(() => import('@/Components/Marketing/SaasLandingSection'));
const CtaBanner          = lazy(() => import('@/Components/Marketing/CtaBanner'));
const TrustStrip         = lazy(() => import('@/Components/Marketing/TrustStrip'));
const Services           = lazy(() => import('@/Components/Marketing/Services'));
const Process            = lazy(() => import('@/Components/Marketing/Process'));
const Portfolio          = lazy(() => import('@/Components/Marketing/Portfolio'));
const CostCalculatorV2   = lazy(() => import('@/Components/Marketing/CostCalculatorV2'));
const Faq                = lazy(() => import('@/Components/Marketing/Faq'));
const Contact            = lazy(() => import('@/Components/Marketing/Contact'));

export default function Welcome({ auth, hero, about, saas_landing, cta_banner, trust_strip, testimonials, services, process, portfolio, faq, cost_calculator_v2, navbar, contact, footer, pricing, strings, steps, extra_sections = [] }) {
    useScrollReveal('.reveal');

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>Website Expert – Web Design & SEO Belfast, Northern Ireland</title>
                <meta name="description" content="Professional web design, e-commerce and SEO services in Belfast and across Northern Ireland. Fixed price, delivered in 2–6 weeks. Free quote in 24 hours — website-expert.uk" />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href="https://website-expert.uk/" />
                <meta property="og:title" content="Website Expert – Web Design Belfast, Northern Ireland" />
                <meta property="og:description" content="Bespoke websites, e-commerce, SEO and Google Ads for Northern Ireland businesses. Fast delivery, fixed prices. Free quote today." />
                <meta property="og:url" content="https://website-expert.uk/" />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content="en_GB" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:title" content="Website Expert – Web Design Belfast" />
                <meta name="twitter:description" content="Bespoke web design, SEO and digital marketing for Northern Ireland businesses. Free quote in 24 hours." />
                <meta name="geo.region" content="GB-NIR" />
                <meta name="geo.placename" content="Belfast, Northern Ireland" />
            </Head>

            {hero           && <Hero data={hero} />}
            {about          && <About data={about} />}
            <Suspense fallback={null}>
                {saas_landing && <SaasLandingSection data={saas_landing} />}
                {cta_banner     && <CtaBanner data={cta_banner} />}
                {trust_strip    && <TrustStrip data={trust_strip} testimonials={testimonials} />}
                {services       && <Services data={services} />}
                {process        && <Process data={process} />}
                {portfolio      && <Portfolio data={portfolio} />}
                {cost_calculator_v2 && <CostCalculatorV2 strings={strings} steps={steps} pricing={pricing} />}
                {faq            && <Faq data={faq} />}
                {contact        && <Contact data={contact} />}
                {extra_sections.map((s) => <GenericSection key={s.key} data={s} />)}
            </Suspense>
        </MarketingLayout>
    );
}
