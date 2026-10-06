import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import Contact from '@/Components/Marketing/Contact';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function ContactPage({ contact = null, footer = null, locale = 'en' }) {
    useScrollReveal('.reveal');

const meta = {
    en: {
        title: 'Contact | Web Design Northern Ireland',
        description: 'Contact Website Expert about your website, web app, SEO or digital marketing project. Book a free 30-minute consultation. We reply within 24 hours.',
    },
    pl: {
        title: 'Kontakt | Strony internetowe w UK',
        description: 'Skontaktuj się z Website Expert w sprawie strony, aplikacji, SEO lub marketingu online. Umów bezpłatną, 30-minutową konsultację. Odpowiadamy w ciągu 24 godzin.',
    },
    pt: {
        title: 'Contacto | Web Design no Reino Unido',
        description: 'Contacte a Website Expert para falar sobre o seu website, aplicação web, SEO ou marketing digital. Marque uma reunião gratuita de 30 minutos. Respondemos em 24 horas.',
    },
};

    const m = meta[locale] ?? meta.en;

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>{m.title}</title>
                <meta name="description" content={m.description} />
                <link rel="canonical" href="/contact" />
            </Head>

            <Contact data={contact} />
        </MarketingLayout>
    );
}
