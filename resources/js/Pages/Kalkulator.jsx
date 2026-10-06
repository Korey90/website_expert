import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function Kalkulator({ auth, footer, pricing, strings, steps, locale = 'en' }) {
    useScrollReveal('.reveal');

    const meta = {
        en: {
            title: 'Website Cost Calculator | Web Design Northern Ireland',
            description: 'Estimate the cost of your website, e-commerce store or web application. Fast, free and no sign-up required. Get a full quote in 24 hours.',
        },
        pl: {
            title: 'Kalkulator kosztów | Strony internetowe w UK',
            description: 'Oszacuj koszt swojej strony internetowej, sklepu e-commerce lub aplikacji webowej. Szybko, bezpłatnie i bez rejestracji. Otrzymaj pełną wycenę w ciągu 24 godzin.',
        },
        pt: {
            title: 'Calculadora de Custos | Web Design no Reino Unido',
            description: 'Estime o custo do seu website, loja online ou aplicação web. Rápido, gratuito e sem necessidade de registo. Obtenha um orçamento completo em 24 horas.',
        },
    };

    const m = meta[locale] ?? meta.en;

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>{m.title}</title>
                <meta name="description" content={m.description} />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href="https://website-expert.uk/calculate" />
            </Head>

            <main className="flex-1">
                <CostCalculatorV2 strings={strings} steps={steps} pricing={pricing} />
            </main>
        </MarketingLayout>
    );
}
