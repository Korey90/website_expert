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
                <title>WebsiteExpert – Professional Web Development UK</title>
                <meta name="description" content="Bespoke web design and development for UK businesses." />
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
