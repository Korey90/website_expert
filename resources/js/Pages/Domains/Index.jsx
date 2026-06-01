import { Head, Link, router, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { useState } from 'react';
import useScrollReveal from '@/Hooks/useScrollReveal';

const POPULAR_TLDS = ['.co.uk', '.uk', '.com', '.net', '.org'];

const FEATURE_ICONS = {
    shield: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
    ),
    globe: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
    ),
    bell: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    ),
    settings: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
    ),
};

const LABELS = {
    en: {
        badge:            'UK Domain Registration',
        heroTitle1:       'Your domain.',
        heroTitle2:       'Your brand.',
        heroSubtitle:     'Professional domain registration with free WHOIS privacy, full DNS control, and expert support — fully managed for UK businesses.',
        fromPrice:        (p) => `from £${p}/year · includes WHOIS privacy`,
        searchBtn:        'Search Domain',
        searchPlaceholder:'yourname, yourcompany, yourbrand…',
        pricingLabel:     'Pricing',
        pricingTitle:     'Popular Domain Extensions',
        pricingSubtitle:  'All prices include WHOIS privacy protection and full DNS management. No hidden fees.',
        renewLabel:       'Renew:',
        searchTld:        (tld) => `Search ${tld}`,
        includedLabel:    'Included',
        includedTitle:    'Everything You Need',
        includedSubtitle: 'Every domain comes with these features as standard — no upsells, no surprises.',
        bundlesLabel:     'Bundles',
        bundlesTitle:     'Need More Than Just a Domain?',
        bundlesDesc:      'Our Website Launch Package includes domain registration, hosting, SSL certificate, business email, and SEO setup — everything managed by our team.',
        bundlesPackage:   'Website Launch Package',
        bundlesCta:       'Get a Bundle Quote',
        bundlesServices:  'View All Services',
        faqLabel:         'FAQ',
        faqTitle:         'Common Questions',
        faqSubtitle:      "Still have questions? We're happy to help.",
        contactUs:        'Contact Us',
        metaTitle:        'Domain Registration UK — Register Your Business Domain',
        metaDesc:         'Professional UK domain registration with free WHOIS privacy, DNS management, and auto-renewal reminders. Fully managed for UK businesses.',
        features: [
            { icon: 'shield',   title: 'Free WHOIS Privacy',       desc: 'Keep your personal contact details hidden from public lookup at no extra cost.' },
            { icon: 'globe',    title: 'Full DNS Management',       desc: 'Control every DNS record — A, MX, CNAME, TXT — directly from your portal.' },
            { icon: 'bell',     title: 'Auto-Renewal Reminders',   desc: 'We email you at 30, 14, 7, and 1 day before expiry so you never lose your domain.' },
            { icon: 'settings', title: 'Fully Managed Service',    desc: 'Our team handles registration, transfers, and renewals on your behalf.' },
        ],
        faq: [
            { q: 'How long does domain registration take?',  a: 'Most domains are registered within minutes. Some TLDs may take up to 24 hours.' },
            { q: 'Can I transfer an existing domain?',        a: "Yes — we support domain transfers. You'll need your auth/EPP code from your current registrar." },
            { q: 'What happens when my domain expires?',      a: 'We send renewal reminders at 30, 14, 7, and 1 day before expiry. Auto-renewal is available on request.' },
            { q: 'Do you offer domain + hosting bundles?',    a: 'Yes — ask us about our Website Launch Package which includes domain, hosting, SSL, business email, and SEO setup.' },
        ],
    },
    pl: {
        badge:            'Rejestracja domen',
        heroTitle1:       'Twoja domena.',
        heroTitle2:       'Twoja marka.',
        heroSubtitle:     'Profesjonalna rejestracja domen z darmową ochroną WHOIS, pełnym zarządzaniem DNS i pomocą ekspertów — całkowita obsługa dla firm w UK.',
        fromPrice:        (p) => `od £${p}/rok · z ochroną WHOIS`,
        searchBtn:        'Szukaj domeny',
        searchPlaceholder:'twojanazwa, twojaFirma, twojaMarka…',
        pricingLabel:     'Cennik',
        pricingTitle:     'Popularne rozszerzenia domen',
        pricingSubtitle:  'Wszystkie ceny zawierają ochronę WHOIS i zarządzanie DNS. Bez ukrytych opłat.',
        renewLabel:       'Odnowienie:',
        searchTld:        (tld) => `Szukaj ${tld}`,
        includedLabel:    'W zestawie',
        includedTitle:    'Wszystko, czego potrzebujesz',
        includedSubtitle: 'Każda domena zawiera te funkcje w standardzie — bez dodatków, bez niespodzianek.',
        bundlesLabel:     'Pakiety',
        bundlesTitle:     'Potrzebujesz czegoś więcej niż domeny?',
        bundlesDesc:      'Nasz pakiet Website Launch obejmuje rejestrację domeny, hosting, certyfikat SSL, firmową pocztę e-mail i konfigurację SEO — wszystko zarządzane przez nasz zespół.',
        bundlesPackage:   'Website Launch Package',
        bundlesCta:       'Zapytaj o pakiet',
        bundlesServices:  'Zobacz wszystkie usługi',
        faqLabel:         'FAQ',
        faqTitle:         'Najczęstsze pytania',
        faqSubtitle:      'Masz więcej pytań? Chętnie pomożemy.',
        contactUs:        'Skontaktuj się',
        metaTitle:        'Rejestracja domen UK — Zarejestruj domenę dla swojej firmy',
        metaDesc:         'Profesjonalna rejestracja domen w UK z darmową ochroną WHOIS, zarządzaniem DNS i przypomnieniami o odnowieniu.',
        features: [
            { icon: 'shield',   title: 'Darmowa ochrona WHOIS',    desc: 'Twoje dane kontaktowe są ukryte przed publicznym wyszukiwaniem bez żadnych dodatkowych kosztów.' },
            { icon: 'globe',    title: 'Pełne zarządzanie DNS',     desc: 'Kontroluj każdy rekord DNS — A, MX, CNAME, TXT — bezpośrednio z panelu klienta.' },
            { icon: 'bell',     title: 'Przypomnienia o odnowieniu', desc: 'Wysyłamy e-mail 30, 14, 7 i 1 dzień przed wygaśnięciem, abyś nigdy nie stracił domeny.' },
            { icon: 'settings', title: 'Pełna obsługa',             desc: 'Nasz zespół zajmuje się rejestracją, transferem i odnowieniem domen w Twoim imieniu.' },
        ],
        faq: [
            { q: 'Jak długo trwa rejestracja domeny?',         a: 'Większość domen jest rejestrowanych w ciągu kilku minut. Niektóre rozszerzenia mogą zająć do 24 godzin.' },
            { q: 'Czy mogę przenieść istniejącą domenę?',      a: 'Tak — obsługujemy transfery domen. Będziesz potrzebować kodu auth/EPP od swojego obecnego rejestratora.' },
            { q: 'Co się dzieje, gdy moja domena wygasa?',     a: 'Wysyłamy przypomnienia o odnowieniu na 30, 14, 7 i 1 dzień przed wygaśnięciem. Na życzenie dostępne jest automatyczne odnowienie.' },
            { q: 'Czy oferujecie pakiety z domeną i hostingiem?', a: 'Tak — zapytaj o nasz Website Launch Package, który obejmuje domenę, hosting, SSL, firmową pocztę i konfigurację SEO.' },
        ],
    },
    pt: {
        badge:            'Registo de Domínios UK',
        heroTitle1:       'O seu domínio.',
        heroTitle2:       'A sua marca.',
        heroSubtitle:     'Registo profissional de domínios com privacidade WHOIS gratuita, controlo DNS completo e suporte especializado — totalmente gerido para empresas no Reino Unido.',
        fromPrice:        (p) => `a partir de £${p}/ano · inclui privacidade WHOIS`,
        searchBtn:        'Pesquisar Domínio',
        searchPlaceholder:'seunome, suaempresa, suamarca…',
        pricingLabel:     'Preços',
        pricingTitle:     'Extensões de Domínio Populares',
        pricingSubtitle:  'Todos os preços incluem proteção de privacidade WHOIS e gestão de DNS. Sem taxas ocultas.',
        renewLabel:       'Renovação:',
        searchTld:        (tld) => `Pesquisar ${tld}`,
        includedLabel:    'Incluído',
        includedTitle:    'Tudo o que Precisa',
        includedSubtitle: 'Cada domínio inclui estas funcionalidades como padrão — sem extras, sem surpresas.',
        bundlesLabel:     'Pacotes',
        bundlesTitle:     'Precisa de Mais do que um Domínio?',
        bundlesDesc:      'O nosso Website Launch Package inclui registo de domínio, alojamento, certificado SSL, email empresarial e configuração de SEO — tudo gerido pela nossa equipa.',
        bundlesPackage:   'Website Launch Package',
        bundlesCta:       'Pedir Orçamento de Pacote',
        bundlesServices:  'Ver Todos os Serviços',
        faqLabel:         'FAQ',
        faqTitle:         'Perguntas Comuns',
        faqSubtitle:      'Ainda tem dúvidas? Estamos felizes em ajudar.',
        contactUs:        'Contacte-nos',
        metaTitle:        'Registo de Domínios UK — Registe o Domínio da Sua Empresa',
        metaDesc:         'Registo profissional de domínios no Reino Unido com privacidade WHOIS gratuita, gestão de DNS e lembretes de renovação automática.',
        features: [
            { icon: 'shield',   title: 'Privacidade WHOIS Gratuita',  desc: 'Mantenha os seus dados de contacto ocultos das pesquisas públicas sem custos adicionais.' },
            { icon: 'globe',    title: 'Gestão DNS Completa',         desc: 'Controle cada registo DNS — A, MX, CNAME, TXT — diretamente a partir do seu portal.' },
            { icon: 'bell',     title: 'Lembretes de Renovação',      desc: 'Enviamos e-mail 30, 14, 7 e 1 dia antes do vencimento para nunca perder o seu domínio.' },
            { icon: 'settings', title: 'Serviço Totalmente Gerido',   desc: 'A nossa equipa trata do registo, transferências e renovações em seu nome.' },
        ],
        faq: [
            { q: 'Quanto tempo demora o registo de domínio?',           a: 'A maioria dos domínios é registada em minutos. Alguns TLDs podem demorar até 24 horas.' },
            { q: 'Posso transferir um domínio existente?',               a: 'Sim — suportamos transferências de domínios. Precisará do código auth/EPP do seu registador atual.' },
            { q: 'O que acontece quando o meu domínio expira?',          a: 'Enviamos lembretes de renovação a 30, 14, 7 e 1 dia antes do vencimento. A renovação automática está disponível a pedido.' },
            { q: 'Oferecem pacotes de domínio + alojamento?',            a: 'Sim — pergunte sobre o nosso Website Launch Package que inclui domínio, alojamento, SSL, email empresarial e configuração de SEO.' },
        ],
    },
};

function Icon({ name }) {
    return (
        <svg className="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            {FEATURE_ICONS[name]}
        </svg>
    );
}

const BUNDLES = {
    en: [
        {
            key:       'starter',
            name:      'Domain Only',
            badge:     null,
            price:     'from £9.99/yr',
            desc:      'Just the domain — registered, managed, and renewed by our team.',
            features:  ['Domain registration', 'Free WHOIS privacy', 'Full DNS control', 'Renewal reminders'],
            cta:       'Search Domains',
            href:      null,
            type:      'search',
            highlight: false,
        },
        {
            key:       'business',
            name:      'Domain + Email',
            badge:     'Popular',
            price:     'from £24.99/yr',
            desc:      'A domain paired with a professional business email address.',
            features:  ['Everything in Domain Only', 'Business email (5 mailboxes)', 'SSL certificate', 'Anti-spam protection'],
            cta:       'Get a Quote',
            href:      null,
            type:      'contact',
            highlight: true,
        },
        {
            key:       'launch',
            name:      'Website Launch',
            badge:     'Best Value',
            price:     'from £499 project',
            desc:      'Complete online presence — domain, site, email, SSL, and SEO, all managed.',
            features:  ['Everything in Domain + Email', 'Professional website', 'SEO setup', 'Google Analytics'],
            cta:       'Calculate Cost',
            href:      null,
            type:      'calculate',
            highlight: false,
        },
    ],
    pl: [
        {
            key:       'starter',
            name:      'Tylko domena',
            badge:     null,
            price:     'od £9,99/rok',
            desc:      'Sama domena — zarejestrowana, zarządzana i odnawiana przez nasz zespół.',
            features:  ['Rejestracja domeny', 'Darmowa ochrona WHOIS', 'Pełne zarządzanie DNS', 'Przypomnienia o odnowieniu'],
            cta:       'Szukaj domeny',
            href:      null,
            type:      'search',
            highlight: false,
        },
        {
            key:       'business',
            name:      'Domena + Email',
            badge:     'Popularny',
            price:     'od £24,99/rok',
            desc:      'Domena z profesjonalną firmową skrzynką pocztową.',
            features:  ['Wszystko z "Tylko domena"', 'Firmowy email (5 skrzynek)', 'Certyfikat SSL', 'Ochrona antyspamowa'],
            cta:       'Zapytaj o wycenę',
            href:      null,
            type:      'contact',
            highlight: true,
        },
        {
            key:       'launch',
            name:      'Website Launch',
            badge:     'Najlepsza wartość',
            price:     'od £499 projekt',
            desc:      'Kompletna obecność online — domena, strona, email, SSL i SEO.',
            features:  ['Wszystko z "Domena + Email"', 'Profesjonalna strona', 'Konfiguracja SEO', 'Google Analytics'],
            cta:       'Oblicz koszt',
            href:      null,
            type:      'calculate',
            highlight: false,
        },
    ],
    pt: [
        {
            key:       'starter',
            name:      'Domínio',
            badge:     null,
            price:     'a partir de £9,99/ano',
            desc:      'Apenas o domínio — registado, gerido e renovado pela nossa equipa.',
            features:  ['Registo de domínio', 'Privacidade WHOIS gratuita', 'Controlo DNS completo', 'Lembretes de renovação'],
            cta:       'Pesquisar Domínio',
            href:      null,
            type:      'search',
            highlight: false,
        },
        {
            key:       'business',
            name:      'Domínio + Email',
            badge:     'Popular',
            price:     'a partir de £24,99/ano',
            desc:      'Domínio com email empresarial profissional.',
            features:  ['Tudo em Domínio', 'Email empresarial (5 caixas)', 'Certificado SSL', 'Proteção anti-spam'],
            cta:       'Pedir Orçamento',
            href:      null,
            type:      'contact',
            highlight: true,
        },
        {
            key:       'launch',
            name:      'Website Launch',
            badge:     'Melhor Valor',
            price:     'a partir de £499 projeto',
            desc:      'Presença online completa — domínio, site, email, SSL e SEO.',
            features:  ['Tudo em Domínio + Email', 'Site profissional', 'Configuração SEO', 'Google Analytics'],
            cta:       'Calcular Custo',
            href:      null,
            type:      'calculate',
            highlight: false,
        },
    ],
};

function DomainSearchForm({ initialQuery = '', size = 'lg', l }) {
    const [query, setQuery] = useState(initialQuery);

    function handleSubmit(e) {
        e.preventDefault();
        if (!query.trim()) return;
        router.get(route('domains.check'), { q: query.trim() });
    }

    const isLg = size === 'lg';

    return (
        <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3 w-full">
            <input
                type="text"
                value={query}
                onChange={e => setQuery(e.target.value)}
                placeholder={l?.searchPlaceholder ?? 'yourname, yourcompany, yourbrand…'}
                className={`flex-1 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 text-neutral-900 dark:text-white placeholder:text-neutral-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition ${
                    isLg ? 'py-3.5 text-base' : 'py-2.5 text-sm'
                }`}
            />
            <button
                type="submit"
                className={`rounded-xl bg-brand-500 font-semibold text-white hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20 whitespace-nowrap inline-flex items-center gap-2 justify-center ${
                    isLg ? 'px-7 py-3.5 text-base' : 'px-5 py-2.5 text-sm'
                }`}
            >
                {l?.searchBtn ?? 'Search Domain'}
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </button>
        </form>
    );
}

function PriceCard({ tld, register_price, renew_price, currency, l }) {
    const symbol = currency === 'GBP' ? '£' : currency === 'EUR' ? '€' : '$';
    return (
        <div className="group flex flex-col items-center p-6 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-xl hover:shadow-brand-500/5 transition-all text-center">
            <div className="font-display text-xl font-bold text-neutral-900 dark:text-white">{tld}</div>
            <div className="mt-3 font-display text-3xl font-extrabold text-brand-500">
                {symbol}{Number(register_price).toFixed(2)}
                <span className="text-sm font-normal text-neutral-400">/yr</span>
            </div>
            <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                {l?.renewLabel ?? 'Renew:'} {symbol}{Number(renew_price).toFixed(2)}/yr
            </div>
            <Link
                href={`${route('domains.check')}?q=${encodeURIComponent(tld)}`}
                className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-brand-500 hover:text-brand-600 transition-colors group/link"
            >
                {l?.searchTld ? l.searchTld(tld) : `Search ${tld}`}
                <svg className="w-3 h-3 transition-transform group-hover/link:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </Link>
        </div>
    );
}

function FaqItem({ q, a }) {
    const [open, setOpen] = useState(false);
    return (
        <div className="border-b border-neutral-200 dark:border-neutral-800 last:border-b-0">
            <button
                type="button"
                onClick={() => setOpen(o => !o)}
                className="w-full flex items-center justify-between gap-4 py-5 text-left group"
            >
                <span className="font-medium text-neutral-900 dark:text-white group-hover:text-brand-500 transition-colors">{q}</span>
                <span className={`shrink-0 w-6 h-6 rounded-full border flex items-center justify-center transition-transform duration-200 ${
                    open ? 'rotate-45 border-brand-500 text-brand-500' : 'border-neutral-300 dark:border-neutral-600 text-neutral-500'
                }`}>
                    <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </span>
            </button>
            <div className={`overflow-hidden transition-all duration-300 ${open ? 'max-h-60 pb-5' : 'max-h-0'}`}>
                <p className="text-neutral-500 dark:text-neutral-400 text-sm leading-relaxed">{a}</p>
            </div>
        </div>
    );
}

export default function DomainsIndex({ prices = [], auth }) {
    useScrollReveal('.reveal');
    const { footer, locale } = usePage().props;
    const l = LABELS[locale] ?? LABELS.en;
    const popularPrices = (prices || []).filter(p => POPULAR_TLDS.includes(p.tld));
    const minPrice = popularPrices.length
        ? Math.min(...popularPrices.map(p => Number(p.register_price)))
        : null;

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>{l.metaTitle}</title>
                <meta name="description" content={l.metaDesc} />
            </Head>

            {/* Hero */}
            <section className="relative min-h-[60vh] flex items-center overflow-hidden pt-16 pb-20 md:pt-24 md:pb-28">
                <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50/40 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
                <div className="absolute top-1/3 right-0 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl pointer-events-none hidden md:block" aria-hidden="true" />
                <div className="absolute bottom-0 left-10 w-48 h-48 bg-brand-500/5 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />

                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 w-full">
                    <div className="max-w-2xl">
                        <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 mb-6">
                            <span className="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse" />
                            {l.badge}
                        </span>
                        <h1 className="font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight text-neutral-900 dark:text-white">
                            {l.heroTitle1}<br />
                            <span className="text-brand-500">{l.heroTitle2}</span>
                        </h1>
                        <p className="mt-6 text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed">
                            {l.heroSubtitle}
                        </p>
                        {minPrice !== null && (
                            <p className="mt-2 text-sm text-neutral-400">
                                {l.fromPrice(minPrice.toFixed(2))}
                            </p>
                        )}
                        <div className="mt-8 max-w-xl">
                            <DomainSearchForm size="lg" l={l} />
                        </div>
                    </div>
                </div>
            </section>

            {/* Popular TLDs */}
            {popularPrices.length > 0 && (
                <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-14 reveal">
                            <span className="section-label">{l.pricingLabel}</span>
                            <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                                {l.pricingTitle}
                            </h2>
                            <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-lg mx-auto">
                                {l.pricingSubtitle}
                            </p>
                        </div>
                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 reveal">
                            {POPULAR_TLDS.map(tld => {
                                const p = popularPrices.find(x => x.tld === tld);
                                return p ? <PriceCard key={tld} {...p} l={l} /> : null;
                            })}
                        </div>
                    </div>
                </section>
            )}

            {/* What's Included */}
            <section className="py-20 md:py-28 bg-neutral-50/50 dark:bg-neutral-900/30">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-14 reveal">
                        <span className="section-label">{l.includedLabel}</span>
                        <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                            {l.includedTitle}
                        </h2>
                        <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-lg mx-auto">
                            {l.includedSubtitle}
                        </p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal">
                        {l.features.map(f => (
                            <div
                                key={f.title}
                                className="flex flex-col p-7 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-xl hover:shadow-brand-500/5 transition-all"
                            >
                                <div className="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center mb-5 shrink-0">
                                    <Icon name={f.icon} />
                                </div>
                                <h3 className="font-semibold text-neutral-900 dark:text-white mb-2">{f.title}</h3>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed">{f.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Bundle cards */}
            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12 reveal">
                        <span className="section-label justify-center mb-4">{l.bundlesLabel}</span>
                        <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-3">
                            {l.bundlesTitle}
                        </h2>
                        <p className="text-neutral-500 dark:text-neutral-400 max-w-xl mx-auto">
                            {l.bundlesDesc}
                        </p>
                    </div>

                    <div className="grid gap-8 md:grid-cols-3">
                        {(BUNDLES[locale] ?? BUNDLES.en).map((bundle) => (
                            <div
                                key={bundle.key}
                                className={`relative flex flex-col rounded-2xl border p-8 reveal transition-shadow hover:shadow-lg ${
                                    bundle.highlight
                                        ? 'border-brand-500 bg-brand-500/5 dark:bg-brand-500/10 shadow-brand-500/10 shadow-md'
                                        : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900'
                                }`}
                            >
                                {bundle.badge && (
                                    <span className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-xs font-bold bg-brand-500 text-white shadow-sm">
                                        {bundle.badge}
                                    </span>
                                )}
                                <p className="text-xs font-semibold text-brand-500 uppercase tracking-widest mb-1">{bundle.price}</p>
                                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-2">{bundle.name}</h3>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-6">{bundle.desc}</p>
                                <ul className="space-y-2 mb-8 flex-1">
                                    {bundle.features.map((f) => (
                                        <li key={f} className="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
                                            <svg className="w-4 h-4 text-brand-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {f}
                                        </li>
                                    ))}
                                </ul>
                                {bundle.type === 'search' && (
                                    <a
                                        href="#search"
                                        className="mt-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-brand-500 text-brand-500 font-semibold text-sm hover:bg-brand-500 hover:text-white active:scale-95 transition-all"
                                    >
                                        {bundle.cta}
                                    </a>
                                )}
                                {bundle.type === 'contact' && (
                                    <Link
                                        href={route('contact.index')}
                                        className="mt-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-500 text-white font-semibold text-sm hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                                    >
                                        {bundle.cta}
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </Link>
                                )}
                                {bundle.type === 'calculate' && (
                                    <Link
                                        href={route('home') + '#calculate'}
                                        className="mt-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-neutral-300 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 font-semibold text-sm hover:border-brand-500 hover:text-brand-500 dark:hover:text-brand-400 active:scale-95 transition-all"
                                    >
                                        {bundle.cta}
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* FAQ */}
            <section className="py-20 md:py-28 bg-neutral-50/50 dark:bg-neutral-900/30">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-3 gap-12 lg:gap-20">
                        <div className="reveal lg:sticky lg:top-28 lg:self-start">
                            <span className="section-label">{l.faqLabel}</span>
                            <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                                {l.faqTitle}
                            </h2>
                            <p className="mt-4 text-neutral-500 dark:text-neutral-400 text-sm">
                                {l.faqSubtitle}
                            </p>
                            <Link
                                href={route('contact.index')}
                                className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors"
                            >
                                {l.contactUs}
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </Link>
                        </div>
                        <div className="lg:col-span-2 reveal">
                            {l.faq.map(item => <FaqItem key={item.q} {...item} />)}
                        </div>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
