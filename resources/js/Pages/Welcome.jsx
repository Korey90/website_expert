import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import Hero           from '@/Components/Marketing/Hero';
import About          from '@/Components/Marketing/About';
import CtaBanner      from '@/Components/Marketing/CtaBanner';
import TrustStrip     from '@/Components/Marketing/TrustStrip';
import Services       from '@/Components/Marketing/Services';
import Portfolio      from '@/Components/Marketing/Portfolio';
import CostCalculator from '@/Components/Marketing/CostCalculator';
import Contact        from '@/Components/Marketing/Contact';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Welcome({ auth, hero, about, cta_banner, trust_strip, testimonials, services, portfolio, cost_calculator, navbar, contact, footer }) {
    useScrollReveal('.reveal');

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>WebsiteExpert – Professional Web Development UK</title>
                <meta name="description" content="Bespoke web design and development for UK businesses." />
            </Head>

            <Hero data={hero} />
            <About data={about} />
            <CtaBanner data={cta_banner} />
            <TrustStrip data={trust_strip} testimonials={testimonials} />
            <Services data={services} />
            <Portfolio data={portfolio} />
            <CostCalculator data={cost_calculator} />
            <Contact data={contact} />
        </MarketingLayout>
    );
}
