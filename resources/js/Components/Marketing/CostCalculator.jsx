import { useState, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { pushEvent } from '@/utils/dataLayer';

const fmt = v =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', minimumFractionDigits: 0 }).format(v);

const PRICING = {
    projectType: {
        wizytowka: { label_en: 'Brochure Website',  label_pl: 'Strona wizytówkowa', icon: '🌐', base: 800,  desc_en: '5–10 pages, ideal for small businesses and freelancers',                      desc_pl: '5–10 podstron, idealna dla małych firm i freelancerów' },
        landing:   { label_en: 'Landing Page',       label_pl: 'Landing page',       icon: '🎯', base: 400,  desc_en: 'Single page focused on one goal — lead capture or sales',                     desc_pl: 'Jedna strona skupiona na jednym celu – pozyskanie leadu lub sprzedaż' },
        ecommerce: { label_en: 'E-Commerce Store',   label_pl: 'Sklep e-commerce',   icon: '🛒', base: 3000, desc_en: 'Products, cart, online payments and order management panel',                   desc_pl: 'Produkty, koszyk, płatności online i panel zarządzania zamówieniami' },
        aplikacja: { label_en: 'Web Application',    label_pl: 'Aplikacja webowa',   icon: '⚙️',  base: 7500, desc_en: 'Complex platform with business logic, user roles and API',                     desc_pl: 'Złożona platforma z logiką biznesową, rolami użytkowników i API' },
        blog:      { label_en: 'Blog / Portal',      label_pl: 'Blog / Portal',      icon: '📰', base: 2500, desc_en: 'Articles, categories, comments and SEO-friendly URLs',                        desc_pl: 'Artykuły, kategorie, komentarze i SEO-friendly URL-e' },
    },
    design: {
        template: { label_en: 'Ready-made Template', label_pl: 'Gotowy szablon',       icon: '📋', multiplier: 1.0, desc_en: 'A proven layout — faster delivery at a lower price',                         desc_pl: 'Sprawdzony układ graficzny – szybka realizacja w niższej cenie' },
        custom:   { label_en: 'Custom Design',        label_pl: 'Design custom',        icon: '🎨', multiplier: 1.5, desc_en: 'Graphic design created from scratch, tailored to your brand',                desc_pl: 'Projekt graficzny stworzony od zera, dopasowany do Twojej marki' },
        premium:  { label_en: 'Premium UI/UX Design', label_pl: 'Design premium UI/UX', icon: '✨', multiplier: 2.1, desc_en: 'Bespoke design with full UX research, wireframes and prototyping',            desc_pl: 'Autorski design z pełnym procesem badań UX, wireframes i prototypowaniem' },
    },
    cms: {
        none:     { label_en: 'No CMS',          label_pl: 'Brak CMS',          icon: '❌', cost: 0,    desc_en: 'Static site — content changes require a developer',                             desc_pl: 'Strona statyczna – zmiany treści wymagają ingerencji programisty' },
        basic:    { label_en: 'Basic CMS',        label_pl: 'Prosty CMS',         icon: '🗂️', cost: 300,  desc_en: 'WordPress or similar — edit text and images without coding',                    desc_pl: 'WordPress lub podobny – edycja tekstów i zdjęć bez kodowania' },
        advanced: { label_en: 'Advanced CMS',     label_pl: 'Zaawansowany CMS',   icon: '💡', cost: 1200, desc_en: 'Custom CMS with visual editor, user roles and publishing workflow',              desc_pl: 'Własny CMS z edytorem wizualnym, rolami użytkowników i workflow publikacji' },
    },
    integrations: {
        payment:    { label_en: 'Online Payments',       label_pl: 'Płatności online',      icon: '💳', cost: 900,  desc_en: 'Stripe, PayPal, Przelewy24 — secure transactions on your site',              desc_pl: 'Stripe, PayPal, Przelewy24 – bezpieczne transakcje bezpośrednio na stronie' },
        api:        { label_en: 'API Integrations',      label_pl: 'Integracje API',         icon: '🔗', cost: 1200, desc_en: 'Connect with external services and systems via REST or GraphQL',              desc_pl: 'Połączenie z zewnętrznymi serwisami i systemami przez REST lub GraphQL' },
        crm:        { label_en: 'CRM / ERP',             label_pl: 'CRM / ERP',             icon: '📊', cost: 1500, desc_en: 'Sync leads and data with HubSpot, Salesforce or your own system',            desc_pl: 'Synchronizacja leadów i danych z HubSpot, Salesforce lub własnym systemem' },
        newsletter: { label_en: 'Newsletter / Mailing',  label_pl: 'Newsletter / Mailing',  icon: '📧', cost: 500,  desc_en: 'Mailchimp, ConvertKit or your own email campaign automation',                desc_pl: 'Mailchimp, ConvertKit lub własny system automatyzacji kampanii e-mail' },
        analytics:  { label_en: 'Analytics (GA4/Pixel)', label_pl: 'Analytics (GA4/Pixel)', icon: '📈', cost: 300,  desc_en: 'Google Analytics 4 and Meta Pixel — track traffic and campaign results',      desc_pl: 'Google Analytics 4 i Meta Pixel – śledzenie ruchu i efektywności kampanii' },
    },
    seoPackage: {
        none:     { label_en: 'No SEO',          label_pl: 'Bez SEO',          icon: '—',  cost: 0,    desc_en: 'No optimisation — suitable for internal projects or intranets',                  desc_pl: 'Bez optymalizacji – odpowiednie dla projektów wewnętrznych lub intranetów' },
        basic:    { label_en: 'Basic SEO',        label_pl: 'SEO podstawowe',   icon: '🔍', cost: 600,  desc_en: 'Meta tags, XML sitemap, schema.org and page speed optimisation',                 desc_pl: 'Tagi meta, sitemap XML, schema.org i optymalizacja prędkości ładowania' },
        advanced: { label_en: 'Advanced SEO',     label_pl: 'SEO zaawansowane', icon: '🚀', cost: 1800, desc_en: 'Keyword audit, internal linking and content marketing strategy',                  desc_pl: 'Audyt słów kluczowych, linkowanie wewnętrzne i strategia content marketingowa' },
    },
    deadline: {
        standard: { label_en: 'Standard (6–8 wks)',    label_pl: 'Standardowy (6–8 tyg.)',   icon: '📅', multiplier: 1.0, desc_en: 'Optimal project flow with time for testing and your feedback',               desc_pl: 'Optymalny przebieg projektu z czasem na testy i Twój feedback' },
        fast:     { label_en: 'Accelerated (3–4 wks)', label_pl: 'Przyspieszony (3–4 tyg.)', icon: '⚡', multiplier: 1.3, desc_en: 'Higher priority and intensive work mode — quick feedback required',           desc_pl: 'Wyższy priorytet i praca w intensywnym trybie – wymaga szybkiego feedbacku' },
        urgent:   { label_en: 'Urgent (1–2 wks)',      label_pl: 'Pilny (1–2 tyg.)',         icon: '🔥', multiplier: 1.6, desc_en: 'Full team mobilisation — decisions must be made immediately',                 desc_pl: 'Pełna mobilizacja zespołu 24/7 – decyzje muszą być podejmowane na bieżąco' },
    },
    hosting: {
        none:  { label_en: 'Own Hosting',           label_pl: 'Własny hosting',         icon: '🏠', cost: 0,  desc_en: 'You already have a server or use a cloud provider (AWS, GCP, etc.)',          desc_pl: 'Masz już własny serwer lub korzystasz z dostawcy chmury (AWS, GCP itp.)' },
        basic: { label_en: 'Hosting Basic (£4/mo)', label_pl: 'Hosting Basic (£4/mo.)', icon: '💾', cost: 48, desc_en: 'SSD, PHP 8.x, SSL certificate, 10 GB space — for small/medium sites',          desc_pl: 'SSD, PHP 8.x, certyfikat SSL, 10 GB przestrzeni – dla małych i średnich stron' },
        pro:   { label_en: 'Hosting Pro (£8/mo)',   label_pl: 'Hosting Pro (£8/mo.)',   icon: '🖥️', cost: 96, desc_en: 'Powerful VPS with daily backup, uptime monitoring and dedicated IP',             desc_pl: 'Wydajny VPS z codziennym backupem, monitoringiem uptime i dedykowanym IP' },
    },
};

const TOTAL_STEPS = 8;

const DEFAULTS = {
    title: { 
        en: 'How Much Will Your Project Cost?',
        pl: 'Ile będzie kosztował Twój projekt?',
        pt: 'Quanto Custará o Seu Projeto?'
    },
    subtitle: { 
        en: 'Answer a few questions and get an instant quote estimate. No registration required.', 
        pl: 'Odpowiedz na kilka pytań i otrzymaj wstępną wycenę. Szybko, bez rejestracji.',
        pt: 'Responda a algumas perguntas e obtenha uma estimativa de orçamento instantânea. Sem necessidade de registro.'
    },
    section_label: { 
        en: 'Cost Calculator',
        pl: 'Kalkulator kosztów',
        pt: 'Calculadora de Custos' 
    },
};

function calcEstimate(a, P) {
    const pt  = P.projectType[a.projectType];
    const des = P.design[a.design];
    const cms = P.cms[a.cms];
    const seo = P.seoPackage[a.seoPackage];
    const dl  = P.deadline[a.deadline];
    const ho  = P.hosting[a.hosting];
    if (!pt || !des || !cms || !seo || !dl || !ho) return null;
    const pagesAddon = Math.max(0, (a.pages - 5)) * 80;
    const intTotal   = (a.integrations || []).reduce((s, k) => s + (P.integrations[k]?.cost || 0), 0);
    const base  = (pt.base + pagesAddon) * des.multiplier + cms.cost + intTotal + seo.cost;
    const total = base * dl.multiplier + ho.cost;
    return { low: Math.round(total * 0.9 / 100) * 100, high: Math.round(total * 1.15 / 100) * 100 };
}

function ProgressBar({ step, stepLabel, ofLabel }) {
    return (
        <div className="mb-6">
            <div className="flex items-center justify-between mb-1">
                <span className="text-xs font-semibold uppercase tracking-widest text-brand-500">
                    {stepLabel} {step} {ofLabel} {TOTAL_STEPS}
                </span>
                <span className="text-xs text-neutral-400">{Math.round(step / TOTAL_STEPS * 100)}%</span>
            </div>
            <div className="w-full h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800">
                <div
                    className="h-1.5 rounded-full transition-all duration-500 bg-brand-500"
                    style={{ width: `${step / TOTAL_STEPS * 100}%` }}
                />
            </div>
        </div>
    );
}

function OptionBtn({ value, selected, onClick, icon, label, desc, sublabel }) {
    return (
        <button
            type="button"
            onClick={() => onClick(value)}
            aria-pressed={selected}
            className={`calc-option-btn${selected ? ' selected' : ''}`}
        >
            <span className="text-xl w-7 text-center shrink-0 mt-0.5" aria-hidden="true">{icon}</span>
            <span className="flex flex-col text-left">
                <span className="font-medium leading-snug">{label}</span>
                {desc    && <span className="text-xs text-neutral-500 dark:text-neutral-400 font-normal mt-0.5 leading-relaxed">{desc}</span>}
                {sublabel && <span className="text-xs text-neutral-400 dark:text-neutral-500 font-semibold mt-1">{sublabel}</span>}
            </span>
        </button>
    );
}

function NavBtns({ onBack, onNext, canNext, nextLabel, backLabel }) {
    return (
        <div className="flex gap-3 mt-8">
            {onBack && (
                <button
                    type="button"
                    onClick={onBack}
                    className="px-5 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:border-brand-500 hover:text-brand-500 transition-colors"
                >
                    {backLabel}
                </button>
            )}
            <button
                type="button"
                onClick={onNext}
                disabled={!canNext}
                className={`flex-1 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all ${canNext ? 'text-white hover:opacity-90 active:scale-95 bg-brand-500' : 'bg-neutral-200 dark:bg-neutral-800 text-neutral-400 cursor-not-allowed'}`}
            >
                {nextLabel}
            </button>
        </div>
    );
}

export default function CostCalculator({ data = null, pricing: pricingProp = null }) {
    const { locale = 'en' } = usePage().props;
    const PRICING_DATA = pricingProp ?? PRICING;

    const t  = (obj, key) => obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';
    const tx = useCallback((key, fallback = '') =>
        data?.extra?.[`${key}_${locale}`] ?? data?.extra?.[`${key}_en`] ?? fallback
    , [data, locale]);

    const [step, setStep]         = useState(1);
    const [a, setA]               = useState({ projectType: '', pages: 5, design: '', cms: '', integrations: [], seoPackage: '', deadline: '', hosting: '', companyName: '', contactEmail: '' });
    const [submitted, setSubmitted] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const set      = useCallback((k, v) => setA(prev => ({ ...prev, [k]: v })), []);
    const toggleInt = useCallback((k) => setA(prev => {
        const list = prev.integrations.includes(k) ? prev.integrations.filter(x => x !== k) : [...prev.integrations, k];
        return { ...prev, integrations: list };
    }), []);

    const next = () => setStep(s => s + 1);
    const back = () => setStep(s => s - 1);
    const reset = () => { setStep(1); setA({ projectType: '', pages: 5, design: '', cms: '', integrations: [], seoPackage: '', deadline: '', hosting: '', companyName: '', contactEmail: '' }); setSubmitted(false); };
    const estimate = calcEstimate(a, PRICING_DATA);

    const handleCalcSubmit = useCallback(async () => {
        if (!a.contactEmail || submitting) return;
        setSubmitting(true);
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            await fetch(route('calculator.lead'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    ...a,
                    estimateLow:  estimate?.low,
                    estimateHigh: estimate?.high,
                }),
            });
        } catch (_) {
            // non-blocking — show success regardless
        } finally {
            setSubmitting(false);
            pushEvent('generate_lead', {
                lead_source:  'calculator',
                project_type: a.projectType,
                estimate_low:  estimate?.low,
                estimate_high: estimate?.high,
            });
            if (typeof window.fbq === 'function') window.fbq('track', 'Lead');
            setSubmitted(true);
        }
    }, [a, estimate, submitting]);

    const d            = data ?? {};
    const extra        = d.extra ?? {};
    const title        = d.title        || DEFAULTS.title[locale]         || DEFAULTS.title.en;
    const subtitle     = d.subtitle     || DEFAULTS.subtitle[locale]      || DEFAULTS.subtitle.en;
    const sectionLabel = t(extra, 'section_label') || DEFAULTS.section_label[locale];

    const stepLabel = tx('step_label', 'Step');
    const ofLabel   = tx('step_of',    'of');
    const backLabel = tx('nav_back',   '← Back');
    const nextLabel = tx('nav_next',   'Next →');
    const skipLabel = tx('nav_skip',   'Skip →');
    const calcLabel = tx('nav_calc',   'Calculate Quote 🚀');

    const stepsData  = extra.steps || [];
    const sq = (i) => stepsData[i]?.[`question_${locale}`] || stepsData[i]?.question_en || '';
    const sh = (i) => stepsData[i]?.[`hint_${locale}`]     || stepsData[i]?.hint_en     || '';

    const baseMultiplierLabel = locale === 'pl' ? 'ceny bazowej'       : locale === 'pt' ? 'do preço base'    : 'of base price';
    const noExtraLabel        = locale === 'pl' ? 'bez dopłaty'        : locale === 'pt' ? 'sem custo extra'  : 'no extra charge';
    const toQuoteLabel        = locale === 'pl' ? 'do wyceny'          : locale === 'pt' ? 'do orçamento'     : 'to quote';
    const standardPricingLabel = locale === 'pl' ? 'standardowa wycena' : locale === 'pt' ? 'preço padrão'   : 'standard pricing';
    const fromLabel           = locale === 'pl' ? 'od'                 : locale === 'pt' ? 'a partir de'      : 'from';

    // Result screen
    if (step > TOTAL_STEPS && estimate) {
        return (
            <div className="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-6 sm:p-8 text-center max-w-2xl mx-auto">
                <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-brand-500/10">
                    <span className="text-3xl">🎯</span>
                </div>
                <h3 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-2">
                    {tx('result_title', 'Your Estimated Quote')}
                </h3>
                <p className="text-neutral-500 dark:text-neutral-400 text-sm mb-6">
                    {tx('result_subtitle', 'Estimate based on the information you provided.')}
                </p>

                <div className="bg-white dark:bg-neutral-950 rounded-xl p-6 mb-6 border border-neutral-100 dark:border-neutral-700">
                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-1">
                        {tx('result_cost_label', 'Estimated project cost')}
                    </p>
                    <p className="font-display text-4xl font-extrabold text-brand-500">{fmt(estimate.low)} – {fmt(estimate.high)}</p>
                    {a.hosting !== 'none' && (
                        <p className="text-xs text-neutral-400 mt-2">
                            {tx('hosting_addon_label', '+ hosting')} {fmt(PRICING_DATA.hosting[a.hosting]?.cost)}{tx('per_year', '/year')}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2 justify-center mb-6">
                    {a.projectType && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-brand-500/10 text-brand-500">
                            {t(PRICING_DATA.projectType[a.projectType], 'label')}
                        </span>
                    )}
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                        {a.pages} {tx('pages_chip', 'pages')}
                    </span>
                    {a.integrations.length > 0 && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                            {a.integrations.length} {tx('integrations_chip', 'integrations')}
                        </span>
                    )}
                    {a.deadline && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                            {t(PRICING_DATA.deadline[a.deadline], 'label')}
                        </span>
                    )}
                </div>

                {!submitted ? (
                    <div>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                            {tx('contact_title', "Enter your details and we'll send you a detailed quote.")}
                        </p>
                        <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-4">
                            <input
                                type="text"
                                placeholder={tx('name_placeholder', 'Your name / company')}
                                value={a.companyName}
                                onChange={e => set('companyName', e.target.value)}
                                className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            <input
                                type="email"
                                placeholder={tx('email_placeholder', 'Your email')}
                                value={a.contactEmail}
                                onChange={e => set('contactEmail', e.target.value)}
                                className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                        </div>
                        <button
                            type="button"
                            disabled={!a.contactEmail || submitting}
                            onClick={handleCalcSubmit}
                            className={`inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-sm transition-all ${a.contactEmail && !submitting ? 'bg-brand-500 text-white hover:opacity-90 active:scale-95' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}
                        >
                            {submitting ? (tx('submitting_btn', 'Sending…')) : tx('submit_btn', 'Send enquiry 🚀')}
                        </button>
                    </div>
                ) : (
                    <div className="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800">
                        <p className="text-green-700 dark:text-green-400 font-semibold text-sm">
                            {tx('success_msg', "✓ Done! We'll get back to you within 1 business day.")}
                        </p>
                        <p className="text-green-600 dark:text-green-500 text-xs mt-1">
                            {tx('sent_to', 'Sent to:')} {a.contactEmail}
                        </p>
                    </div>
                )}

                <button
                    type="button"
                    onClick={reset}
                    className="mt-4 text-xs text-neutral-400 hover:text-brand-500 transition-colors underline underline-offset-2"
                >
                    {tx('restart', 'Start over')}
                </button>
            </div>
        );
    }

    const steps = {
        1: (
            <>
                <ProgressBar step={1} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(0) || 'What type of project do you need?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(0)}</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(PRICING_DATA.projectType).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.projectType === k} onClick={val => set('projectType', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={`${fromLabel} ${fmt(v.base)}`} />
                    ))}
                </div>
                <NavBtns onNext={next} canNext={!!a.projectType} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        2: (
            <>
                <ProgressBar step={2} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(1) || 'How many pages do you need?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(1)}</p>
                <div className="flex flex-col items-center gap-4 py-4">
                    <span className="font-display text-5xl font-bold text-brand-500" aria-live="polite">{a.pages}</span>
                    <input type="range" min={1} max={50} step={1} value={a.pages} onChange={e => set('pages', Number(e.target.value))}
                        className="w-full max-w-xs" aria-label={sq(1) || 'Number of pages'} />
                    <div className="flex justify-between w-full max-w-xs text-xs text-neutral-400">
                        <span>1</span><span>10</span><span>25</span><span>50+</span>
                    </div>
                    {a.pages > 5 && <p className="text-xs text-neutral-500">{tx('pages_addon', 'Each page above 5: +£80')}</p>}
                </div>
                <NavBtns onBack={back} onNext={next} canNext nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        3: (
            <>
                <ProgressBar step={3} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(2) || 'What level of design do you need?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(2)}</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING_DATA.design).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.design === k} onClick={val => set('design', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={v.multiplier > 1.0 ? `×${v.multiplier.toFixed(1)} ${baseMultiplierLabel}` : undefined} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.design} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        4: (
            <>
                <ProgressBar step={4} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(3) || 'Do you need a content management system?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(3)}</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING_DATA.cms).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.cms === k} onClick={val => set('cms', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : noExtraLabel} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.cms} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        5: (
            <>
                <ProgressBar step={5} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(4) || 'Which integrations do you need?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(4)}</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(PRICING_DATA.integrations).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.integrations.includes(k)} onClick={() => toggleInt(k)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={`+${fmt(v.cost)}`} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext nextLabel={a.integrations.length > 0 ? nextLabel : skipLabel} backLabel={backLabel} />
            </>
        ),
        6: (
            <>
                <ProgressBar step={6} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(5) || 'Do you want an SEO package?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(5)}</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING_DATA.seoPackage).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.seoPackage === k} onClick={val => set('seoPackage', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : noExtraLabel} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.seoPackage} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        7: (
            <>
                <ProgressBar step={7} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(6) || 'When do you need the project?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(6)}</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING_DATA.deadline).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.deadline === k} onClick={val => set('deadline', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={v.multiplier > 1 ? `+${Math.round((v.multiplier - 1) * 100)}% ${toQuoteLabel}` : standardPricingLabel} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.deadline} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),
        8: (
            <>
                <ProgressBar step={8} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(7) || 'Hosting & maintenance?'}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(7)}</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING_DATA.hosting).map(([k, v]) => (
                        <OptionBtn key={k} value={k} selected={a.hosting === k} onClick={val => set('hosting', val)}
                            icon={v.icon} label={t(v, 'label')} desc={t(v, 'desc')}
                            sublabel={v.cost > 0 ? `${fmt(v.cost)}${tx('per_year', '/year')}` : 'self-managed'} />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.hosting} nextLabel={calcLabel} backLabel={backLabel} />
            </>
        ),
    };

    return (
        <section id="kalkulator" className="py-20 md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-12 reveal">
                    <span className="section-label">{sectionLabel}</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                    <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                        {subtitle}
                    </p>
                </div>

                <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 shadow-sm reveal"
                    role="form" aria-label={sectionLabel}>
                    {steps[step] || null}
                </div>
            </div>
        </section>
    );
}
