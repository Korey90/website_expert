import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Kalkulator({ auth, footer, pricing, strings, steps }) {
    useScrollReveal('.reveal');

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>Website Cost Calculator – Website Expert</title>
                <meta name="description" content="Estimate the cost of your website, e-commerce store or web application. Fast, free and no sign-up required. Get a full quote in 24 hours." />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href="https://website-expert.uk/calculate" />
            </Head>

            <main className="flex-1">
                <CostCalculatorV2 strings={strings} steps={steps} pricing={pricing} />
            </main>
        </MarketingLayout>
    );
}
