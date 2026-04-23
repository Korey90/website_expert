import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import About from '@/Components/Marketing/About';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function AboutUsPage({ about = null, footer = null, locale = 'en' }) {
    useScrollReveal('.reveal');

    const meta = {
        en: {
            title:       'About Us – Website Expert',
            description: '10+ years building websites for businesses across Northern Ireland & the UK. Fixed price, direct developer contact. Learn about our team and approach.',
        },
        pl: {
            title:       'O nas – Website Expert',
            description: 'Ponad 10 lat tworzenia stron internetowych dla firm z całego UK. Stała cena, bezpośredni kontakt z deweloperem. Poznaj nasz zespół i podejście do pracy.',
        },
        pt: {
            title:       'Sobre Nós – Website Expert',
            description: 'Mais de 10 anos a criar sites para empresas em todo o Reino Unido. Preço fixo, contacto direto com o programador. Conheça a nossa equipa.',
        },
    };

    const m = meta[locale] ?? meta.en;

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>{m.title}</title>
                <meta name="description" content={m.description} />
                <link rel="canonical" href="/about-us" />
            </Head>

            <About data={about} />
        </MarketingLayout>
    );
}
