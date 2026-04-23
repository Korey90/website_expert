import { Head } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import Contact from '@/Components/Marketing/Contact';
import useScrollReveal from '@/Hooks/useScrollReveal';

export default function ContactPage({ contact = null, footer = null, locale = 'en' }) {
    useScrollReveal('.reveal');

    const meta = {
        en: {
            title:       'Contact Us – Website Expert',
            description: 'Get in touch with Website Expert. Free consultation for web design, SEO and digital marketing projects. We reply within 24 business hours.',
        },
        pl: {
            title:       'Kontakt – Website Expert',
            description: 'Skontaktuj się z Website Expert. Bezpłatna konsultacja dla projektów web design, SEO i marketingu cyfrowego. Odpowiadamy w ciągu 24 godzin roboczych.',
        },
        pt: {
            title:       'Contacto – Website Expert',
            description: 'Entre em contacto com o Website Expert. Consulta gratuita para projetos de web design, SEO e marketing digital. Respondemos em 24 horas úteis.',
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
