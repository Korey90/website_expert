import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import Hero           from '@/Components/Marketing/Hero';
import About          from '@/Components/Marketing/About';
import CtaBanner      from '@/Components/Marketing/CtaBanner';
import SaasLandingSection from '@/Components/Marketing/SaasLandingSection';
import TrustStrip     from '@/Components/Marketing/TrustStrip';
import Services       from '@/Components/Marketing/Services';import Process         from '@/Components/Marketing/Process';import Portfolio      from '@/Components/Marketing/Portfolio';
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import Faq              from '@/Components/Marketing/Faq';
import Contact          from '@/Components/Marketing/Contact';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Welcome({ auth, hero, about, cta_banner, trust_strip, testimonials, services, process, portfolio, faq, cost_calculator_v2, navbar, contact, footer, pricing, strings, steps }) {
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
            <SaasLandingSection />
            {cta_banner     && <CtaBanner data={cta_banner} />}
            {trust_strip    && <TrustStrip data={trust_strip} testimonials={testimonials} />}
            {services       && <Services data={services} />}
            {process        && <Process data={process} />}
            {portfolio      && <Portfolio data={portfolio} />}
            {cost_calculator_v2 && <CostCalculatorV2 strings={strings} steps={steps} pricing={pricing} />}
            {faq            && <Faq data={faq} />}
            {contact        && <Contact data={contact} />}
        </MarketingLayout>
    );
}
