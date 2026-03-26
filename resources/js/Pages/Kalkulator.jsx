import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Kalkulator({ auth, navbar, footer, pricing, strings, steps }) {
    useScrollReveal('.reveal');

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>Kalkulator kosztów – WebsiteExpert</title>
                <meta name="description" content="Oblicz orientacyjny koszt swojej strony lub aplikacji internetowej." />
            </Head>

            <main className="flex-1">
                <CostCalculatorV2 strings={strings} steps={steps} pricing={pricing} />
            </main>
        </MarketingLayout>
    );
}
