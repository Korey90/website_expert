import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    title:    { en: 'Simple Process. Brilliant Results.', pl: 'Prosty proces. Doskonałe efekty.', pt: 'Processo Simples. Resultados Brilhantes.' },
    subtitle: { en: "We've refined our process over 10 years to make working with us as smooth as possible.", pl: 'Przez 10 lat doskonaliliśmy nasz proces, by współpraca z nami przebiegała jak najsprawniej.', pt: 'Aperfeiçoámos o nosso processo ao longo de 10 anos para tornar a colaboração connosco o mais fluida possível.' },
    badge:    { en: 'How It Works', pl: 'Jak działamy', pt: 'Como Trabalhamos' },
    steps: [
        { number: '01', title_en: 'Discovery Call',    title_pl: 'Rozmowa wstępna',         title_pt: 'Chamada de Descoberta',      description_en: "We learn about your business, goals, and budget. We'll tell you honestly what's possible and what it will cost.", description_pl: 'Poznajemy Twój biznes, cele i budżet. Powiem szczerze, co jest możliwe i ile to będzie kosztować.', description_pt: 'Aprendemos sobre o seu negócio, objetivos e orçamento. Dizemos honestamente o que é possível e quanto custará.' },
        { number: '02', title_en: 'Quote & Brief',     title_pl: 'Wycena i brief',           title_pt: 'Proposta e Briefing',        description_en: 'You receive a detailed, fixed-price quote within 48 hours. Once approved, we create a full project brief.', description_pl: 'Otrzymujesz szczegółową wycenę ze stałą ceną w ciągu 48 godzin. Po akceptacji tworzymy pełny brief projektu.', description_pt: 'Receberá uma proposta detalhada e de preço fixo em 48 horas. Após aprovação, criamos um briefing completo.' },
        { number: '03', title_en: 'Design',            title_pl: 'Projekt graficzny',        title_pt: 'Design',                     description_en: "We design your website in Figma. You see exactly what you're getting before a line of code is written.", description_pl: 'Projektujemy Twoją stronę w Figma. Widzisz dokładnie, co otrzymasz, zanim zostanie napisana choćby jedna linijka kodu.', description_pt: 'Desenhamos o seu website no Figma. Vê exatamente o que vai receber antes de uma linha de código ser escrita.' },
        { number: '04', title_en: 'Build & Test',      title_pl: 'Budowa i testy',           title_pt: 'Desenvolvimento e Testes',   description_en: 'We build your site, test it across all devices and browsers, and run performance checks.', description_pl: 'Budujemy stronę, testujemy na wszystkich urządzeniach i przeglądarkach oraz sprawdzamy wydajność.', description_pt: 'Construímos o seu site, testamos em todos os dispositivos e browsers, e executamos verificações de desempenho.' },
        { number: '05', title_en: 'Launch & Handover', title_pl: 'Wdrożenie i przekazanie',  title_pt: 'Lançamento e Entrega',       description_en: 'We handle the go-live, provide training, and give you everything you need to manage your site.', description_pl: 'Obsługujemy wdrożenie, przeprowadzamy szkolenie i dajemy Ci wszystko, czego potrzebujesz do zarządzania stroną.', description_pt: 'Tratamos do lançamento, fornecemos formação e damos-lhe tudo o que precisa para gerir o seu site.' },
        { number: '06', title_en: 'Ongoing Support',   title_pl: 'Wsparcie po wdrożeniu',   title_pt: 'Suporte Contínuo',           description_en: "We're not gone after launch. Monthly maintenance plans, updates, and SEO available.", description_pl: 'Nie znikamy po wdrożeniu. Dostępne miesięczne plany utrzymania, aktualizacje i SEO.', description_pt: 'Não desaparecemos após o lançamento. Planos de manutenção mensal, atualizações e SEO disponíveis.' },
    ],
};

function StepCard({ number, title, desc }) {
    return (
        <div className="group p-6 rounded-2xl bg-white dark:bg-neutral-950 border border-neutral-100 dark:border-neutral-800 hover:border-brand-500/40 hover:shadow-xl hover:shadow-brand-500/5 transition-all">
            <p className="font-display font-bold text-3xl text-brand-500/20 group-hover:text-brand-500/40 transition-colors mb-3 leading-none select-none">
                {number}
            </p>
            <h3 className="font-semibold text-neutral-900 dark:text-white mb-2">{title}</h3>
            <p className="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed">{desc}</p>
        </div>
    );
}

export default function Process({ data }) {
    const { locale = 'en' } = usePage().props;

    if (!data) return null;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d     = data ?? {};
    const extra = d.extra ?? {};

    const badge    = DEFAULTS.badge[locale] ?? DEFAULTS.badge.en;
    const title    = d.title    || DEFAULTS.title[locale]    || DEFAULTS.title.en;
    const subtitle = d.subtitle || DEFAULTS.subtitle[locale] || DEFAULTS.subtitle.en;
    const steps    = extra.steps?.length ? extra.steps : DEFAULTS.steps;

    return (
        <section id="process" className="py-20 md:py-28 bg-neutral-50 dark:bg-neutral-900 overflow-hidden">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                {/* Header */}
                <div className="text-center mb-10 sm:mb-16 reveal">
                    <span className="section-label">{badge}</span>
                    <h2 className="font-display text-2xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                    {subtitle && (
                        <p className="mt-3 sm:mt-4 text-sm sm:text-base text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                            {subtitle}
                        </p>
                    )}
                </div>

                {/* Timeline */}
                <div className="relative">
                    {/* Central vertical line — desktop only */}
                    <div className="hidden lg:block absolute left-1/2 top-0 bottom-0 w-px -translate-x-1/2 bg-linear-to-b from-brand-500/40 via-brand-500/20 to-transparent" />

                    {/* ── MOBILE timeline ── */}
                    <div className="lg:hidden relative pl-10">
                        {/* Left accent line */}
                        <div className="absolute left-4 top-2 bottom-2 w-0.5 bg-linear-to-b from-brand-500/60 via-brand-500/20 to-transparent" aria-hidden="true" />

                        <div className="space-y-6">
                            {steps.map((step, i) => {
                                const stepTitle = t(step, 'title')       || '';
                                const stepDesc  = t(step, 'description') || '';
                                const number    = step.number ?? String(i + 1).padStart(2, '0');

                                return (
                                    <div key={i} className="reveal relative">
                                        {/* Number badge pinned to the left line */}
                                        <div className="absolute -left-10 top-0 flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white font-display font-bold text-xs shadow-md shadow-brand-500/30 z-10">
                                            {number}
                                        </div>
                                        {/* Card */}
                                        <div className="rounded-2xl bg-white dark:bg-neutral-950 border border-neutral-100 dark:border-neutral-800 p-4 shadow-sm">
                                            <h3 className="font-semibold text-neutral-900 dark:text-white mb-1 text-sm">{stepTitle}</h3>
                                            <p className="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed">{stepDesc}</p>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* ── DESKTOP timeline (unchanged) ── */}
                    <div className="hidden lg:block space-y-0">
                        {steps.map((step, i) => {
                            const stepTitle = t(step, 'title')       || '';
                            const stepDesc  = t(step, 'description') || '';
                            const number    = step.number ?? String(i + 1).padStart(2, '0');
                            const isRight   = i % 2 === 0;

                            return (
                                <div key={i} className="reveal relative grid grid-cols-2 gap-0 items-center min-h-30">
                                    {/* Left slot */}
                                    <div className="pr-12">
                                        {isRight ? (
                                            <StepCard number={number} title={stepTitle} desc={stepDesc} />
                                        ) : (
                                            <span />
                                        )}
                                    </div>

                                    {/* Centre node */}
                                    <div className="absolute left-1/2 -translate-x-1/2 flex items-center justify-center w-10 h-10 rounded-full bg-brand-500 text-white font-display font-bold text-sm shadow-lg shadow-brand-500/30 z-10 pointer-events-none">
                                        {number}
                                    </div>

                                    {/* Right slot */}
                                    <div className="pl-12">
                                        {!isRight ? (
                                            <StepCard number={number} title={stepTitle} desc={stepDesc} />
                                        ) : (
                                            <span />
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}
