import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import CostCalculator from '@/Components/Marketing/CostCalculator';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Kalkulator({ auth, cost_calculator, navbar, footer }) {
    useScrollReveal('.reveal');

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>Kalkulator kosztów – WebsiteExpert</title>
                <meta name="description" content="Oblicz orientacyjny koszt swojej strony lub aplikacji internetowej." />
            </Head>

            <main className="flex-1">
                <CostCalculator data={cost_calculator} />
            </main>
        </MarketingLayout>
    );
}
