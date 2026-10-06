import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import About from '@/Components/Marketing/About';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function AboutUsPage({ about = null, footer = null, locale = 'en' }) {
    useScrollReveal('.reveal');

    const meta = {
        en: {
            title:       'About Website Expert | Web Development in Northern Ireland',
            description: 'Meet Website Expert - a Dungannon web development studio with 10+ years experience creating websites and web apps across Northern Ireland and the UK.',
        },
        pl: {
            title:       'O nas | Tworzenie stron internetowych w UK',
            description: 'Poznaj Website Expert - tworzymy strony internetowe i aplikacje webowe dla firm w Irlandii Północnej i całym UK. Ponad 10 lat doświadczenia.',
        },
        pt: {
            title:       'Sobre Nós | Criação de Sites no Reino Unido',
            description: 'Conheça a Website Expert - criamos websites e aplicações web para empresas na Irlanda do Norte e em todo o Reino Unido há mais de 10 anos.',
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
