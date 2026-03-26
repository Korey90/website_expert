import { useState } from 'react';
import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    title:       { en: 'Frequently Asked Questions', pl: 'Najczęściej zadawane pytania', pt: 'Perguntas Frequentes' },
    subtitle:    { en: "Can't find what you're looking for? Just ask us.", pl: 'Nie możesz znaleźć odpowiedzi? Po prostu zapytaj.', pt: 'Não encontra o que procura? Basta perguntar-nos.' },
    badge:       { en: 'FAQ', pl: 'FAQ', pt: 'FAQ' },
    button_text: { en: 'Contact Us', pl: 'Skontaktuj się z nami', pt: 'Contacte-nos' },
    button_url:  '/contact',
    items: [
        { question_en: 'How long does a website take?', question_pl: 'Ile czasu trwa stworzenie strony?', question_pt: 'Quanto tempo demora a criar um website?', answer_en: "A standard brochure website typically takes 3–5 weeks from kick-off to launch. E-commerce and web applications vary — we'll give you a clear timeline in your project brief.", answer_pl: 'Standardowa strona wizytówkowa trwa zazwyczaj 3–5 tygodni od startu do wdrożenia. Sklepy i aplikacje mogą trwać dłużej — podamy jasny harmonogram w briefie.', answer_pt: 'Um website institucional padrão demora geralmente 3 a 5 semanas do início ao lançamento. E-commerce e aplicações web variam — forneceremos um cronograma claro no briefing.' },
        { question_en: 'How much does a website cost?', question_pl: 'Ile kosztuje strona internetowa?', question_pt: 'Quanto custa um website?', answer_en: 'Brochure websites start from £799. E-commerce from £2,999. We always provide a fixed-price quote — no surprises.', answer_pl: 'Strony wizytówkowe od £799. Sklepy od £2 999. Zawsze podajemy stałą cenę — bez niespodzianek.', answer_pt: 'Sites institucionais a partir de £799. E-commerce a partir de £2.999. Fornecemos sempre um orçamento de preço fixo — sem surpresas.' },
        { question_en: 'Do you work with clients outside Manchester?', question_pl: 'Czy pracujecie z klientami spoza Manchesteru?', question_pt: 'Trabalham com clientes fora de Manchester?', answer_en: "Yes! The vast majority of our work is done remotely. We work with clients all across the UK. We're always happy to arrange a video call.", answer_pl: 'Tak! Zdecydowana większość naszych projektów jest realizowana zdalnie. Współpracujemy z firmami z całego UK. Zawsze chętnie umawiamy się na videocall.', answer_pt: 'Sim! A grande maioria do nosso trabalho é realizada remotamente. Trabalhamos com clientes em todo o Reino Unido. Estamos sempre disponíveis para uma videochamada.' },
        { question_en: 'What do I need to provide?', question_pl: 'Co muszę dostarczyć?', question_pt: 'O que preciso de fornecer?', answer_en: 'Typically: your logo (or brief for a new one), any text/content for the site, and any specific photos. We can help with content and photography if needed.', answer_pl: 'Zazwyczaj: logo (lub brief do nowego), treści na stronę i ewentualne zdjęcia. W razie potrzeby możemy pomóc z contentem i fotografią.', answer_pt: 'Normalmente: o seu logótipo (ou briefing para um novo), qualquer texto/conteúdo para o site e fotos específicas. Podemos ajudar com conteúdo e fotografia se necessário.' },
        { question_en: 'Will my website work on mobile?', question_pl: 'Czy strona będzie działać na urządzeniach mobilnych?', question_pt: 'O meu website vai funcionar em dispositivos móveis?', answer_en: 'Absolutely. Every website we build is designed mobile-first and tested on a wide range of devices and screen sizes.', answer_pl: 'Oczywiście. Każda strona, którą budujemy, jest projektowana mobile-first i testowana na szerokim zakresie urządzeń i rozmiarów ekranów.', answer_pt: 'Absolutamente. Cada website que construímos é desenhado mobile-first e testado num vasto leque de dispositivos e tamanhos de ecrã.' },
        { question_en: 'Do you offer payment plans?', question_pl: 'Czy oferujecie plany ratalne?', question_pt: 'Oferecem planos de pagamento?', answer_en: "Yes. For larger projects we're happy to discuss staged payments. We typically take a 40–50% deposit, with the balance on delivery or split across milestones.", answer_pl: 'Tak. Przy większych projektach chętnie omawiamy płatności etapowe. Zazwyczaj pobieramy zaliczkę 40–50%, a resztę przy dostarczeniu lub w ratach według kamieni milowych.', answer_pt: 'Sim. Para projetos maiores, estamos dispostos a discutir pagamentos faseados. Tipicamente pedimos um depósito de 40–50%, com o restante na entrega ou dividido por marcos.' },
        { question_en: 'Will I be able to update my website myself?', question_pl: 'Czy będę mógł samodzielnie aktualizować stronę?', question_pt: 'Vou conseguir atualizar o meu website sozinho?', answer_en: 'Yes. We build with a CMS (WordPress or our own Filament-based system) and provide training and a user guide so you can manage your content confidently.', answer_pl: 'Tak. Budujemy z CMS (WordPress lub nasz własny system oparty na Filament) i zapewniamy szkolenie oraz przewodnik, abyś mógł zarządzać treściami samodzielnie.', answer_pt: 'Sim. Construímos com CMS (WordPress ou o nosso próprio sistema baseado em Filament) e fornecemos formação e um guia para que possa gerir o seu conteúdo com confiança.' },
    ],
};

function FaqItem({ question, answer }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="border-b border-neutral-200 dark:border-neutral-800 last:border-b-0">
            <button
                type="button"
                onClick={() => setOpen(o => !o)}
                className="w-full flex items-center justify-between gap-4 py-5 text-left group"
            >
                <span className="font-medium text-neutral-900 dark:text-white group-hover:text-brand-500 transition-colors">
                    {question}
                </span>
                <span className={`shrink-0 w-6 h-6 rounded-full border border-neutral-300 dark:border-neutral-600 flex items-center justify-center transition-transform duration-200 ${open ? 'rotate-45 border-brand-500 text-brand-500' : 'text-neutral-500'}`}>
                    <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </span>
            </button>

            <div className={`overflow-hidden transition-all duration-300 ${open ? 'max-h-96 pb-5' : 'max-h-0'}`}>
                <p className="text-neutral-500 dark:text-neutral-400 leading-relaxed text-sm">
                    {answer}
                </p>
            </div>
        </div>
    );
}

export default function Faq({ data }) {
    const { locale = 'en' } = usePage().props;

    if (!data) return null;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d     = data ?? {};
    const extra = d.extra ?? {};

    const badge      = DEFAULTS.badge[locale]       ?? DEFAULTS.badge.en;
    const title      = d.title       || DEFAULTS.title[locale]       || DEFAULTS.title.en;
    const subtitle   = d.subtitle    || DEFAULTS.subtitle[locale]    || DEFAULTS.subtitle.en;
    const buttonText = d.button_text || DEFAULTS.button_text[locale] || DEFAULTS.button_text.en;
    const buttonUrl  = d.button_url  || DEFAULTS.button_url;
    const items      = extra.items?.length ? extra.items : DEFAULTS.items;

    return (
        <section id="faq" className="py-20 md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid lg:grid-cols-3 gap-12 lg:gap-20">

                    {/* Left column — sticky header */}
                    <div className="reveal lg:sticky lg:top-28 lg:self-start">
                        <span className="section-label">{badge}</span>
                        <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                            {title}
                        </h2>
                        {subtitle && (
                            <p className="mt-4 text-neutral-500 dark:text-neutral-400">
                                {subtitle}
                            </p>
                        )}
                        {buttonText && buttonUrl && (
                            <a
                                href={buttonUrl}
                                className="inline-flex items-center gap-2 mt-8 px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 transition-colors"
                            >
                                {buttonText}
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>
                        )}
                    </div>

                    {/* Right column — accordion */}
                    <div className="lg:col-span-2 reveal">
                        {items.map((item, i) => (
                            <FaqItem
                                key={i}
                                question={t(item, 'question')}
                                answer={t(item, 'answer')}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
