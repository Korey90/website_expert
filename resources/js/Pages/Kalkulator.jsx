import { Head } from '@inertiajs/react';
import Navbar         from '@/Components/Marketing/Navbar';
import CostCalculator from '@/Components/Marketing/CostCalculator';
import Footer         from '@/Components/Marketing/Footer';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Kalkulator({ auth, cost_calculator, navbar, footer }) {
    useScrollReveal('.reveal');

    return (
        <>
            <Head>
                <title>Kalkulator kosztów – WebsiteExpert</title>
                <meta name="description" content="Oblicz orientacyjny koszt swojej strony lub aplikacji internetowej." />
            </Head>

            <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white flex flex-col">
                <Navbar auth={auth} data={navbar} />

                <main className="flex-1">
                    <CostCalculator data={cost_calculator} />
                </main>

                <Footer data={footer} />
            </div>
        </>
    );
}
