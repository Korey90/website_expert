import { Head } from '@inertiajs/react';
import Navbar         from '@/Components/Marketing/Navbar';
import Hero           from '@/Components/Marketing/Hero';
import About          from '@/Components/Marketing/About';
import CtaBanner      from '@/Components/Marketing/CtaBanner';
import TrustStrip     from '@/Components/Marketing/TrustStrip';
import Services       from '@/Components/Marketing/Services';
import Portfolio      from '@/Components/Marketing/Portfolio';
import CostCalculator from '@/Components/Marketing/CostCalculator';
import Contact        from '@/Components/Marketing/Contact';
import Footer         from '@/Components/Marketing/Footer';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Welcome({ auth, hero }) {
    useScrollReveal('.reveal');

    return (
        <>
            <Head>
                <title>WebsiteExpert – Professional Web Development UK</title>
                <meta name="description" content="Bespoke web design and development for UK businesses." />
            </Head>

            <div className="min-h-screen bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white">
                <Navbar auth={auth} />

                <main>
                    <Hero data={hero} />
                    <About />
                    <CtaBanner />
                    <TrustStrip />
                    <Services />
                    <Portfolio />

                    <section id="kalkulator" className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                        <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                            <div className="text-center mb-12 reveal">
                                <span className="section-label">Kalkulator kosztow</span>
                                <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                                    Ile bedzie kosztowal Twoj projekt?
                                </h2>
                                <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                                    Odpowiedz na kilka pytan i otrzymaj wstepna wycene. Szybko, bez rejestracji.
                                </p>
                            </div>
                            <CostCalculator />
                        </div>
                    </section>

                    <Contact />
                </main>

                <Footer />
            </div>
        </>
    );
}
