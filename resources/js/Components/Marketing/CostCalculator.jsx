import { useState, useCallback } from 'react';

const fmt = v => new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', minimumFractionDigits: 0 }).format(v);

const PRICING = {
    projectType: {
        wizytowka: { label: 'Strona wizytówkowa', icon: '🌐', base: 800,  desc: '5–10 podstron, idealna dla małych firm i freelancerów' },
        landing:   { label: 'Landing page',       icon: '🎯', base: 400,  desc: 'Jedna strona skupiona na jednym celu – pozyskanie leadu lub sprzedaż' },
        ecommerce: { label: 'Sklep e-commerce',   icon: '🛒', base: 3000, desc: 'Produkty, koszyk, płatności online i panel zarządzania zamówieniami' },
        aplikacja: { label: 'Aplikacja webowa',   icon: '⚙️',  base: 7500, desc: 'Złożona platforma z logiką biznesową, rolami użytkowników i API' },
        blog:      { label: 'Blog / Portal',      icon: '📰', base: 2500, desc: 'Artykuły, kategorie, komentarze i SEO-friendly URL-e' },
    },
    design: {
        template: { label: 'Gotowy szablon',       icon: '📋', multiplier: 1.0, desc: 'Sprawdzony układ graficzny – szybka realizacja w niższej cenie' },
        custom:   { label: 'Design custom',        icon: '🎨', multiplier: 1.5, desc: 'Projekt graficzny stworzony od zera, dopasowany do Twojej marki' },
        premium:  { label: 'Design premium UI/UX', icon: '✨', multiplier: 2.1, desc: 'Autorski design z pełnym procesem badań UX, wireframes i prototypowaniem' },
    },
    cms: {
        none:     { label: 'Brak CMS',          icon: '❌', cost: 0,    desc: 'Strona statyczna – zmiany treści wymagają ingerencji programisty' },
        basic:    { label: 'Prosty CMS',         icon: '🗂️', cost: 300,  desc: 'WordPress lub podobny – edycja tekstów i zdjęć bez kodowania' },
        advanced: { label: 'Zaawansowany CMS',   icon: '💡', cost: 1200, desc: 'Własny CMS z edytorem wizualnym, rolami użytkowników i workflow publikacji' },
    },
    integrations: {
        payment:    { label: 'Płatności online',      icon: '💳', cost: 900,  desc: 'Stripe, PayPal, Przelewy24 – bezpieczne transakcje bezpośrednio na stronie' },
        api:        { label: 'Integracje API',         icon: '🔗', cost: 1200, desc: 'Połączenie z zewnętrznymi serwisami i systemami przez REST lub GraphQL' },
        crm:        { label: 'CRM / ERP',             icon: '📊', cost: 1500, desc: 'Synchronizacja leadów i danych z HubSpot, Salesforce lub własnym systemem' },
        newsletter: { label: 'Newsletter / Mailing',  icon: '📧', cost: 500,  desc: 'Mailchimp, ConvertKit lub własny system automatyzacji kampanii e-mail' },
        analytics:  { label: 'Analytics (GA4/Pixel)', icon: '📈', cost: 300,  desc: 'Google Analytics 4 i Meta Pixel – śledzenie ruchu i efektywności kampanii' },
    },
    seoPackage: {
        none:     { label: 'Bez SEO',          icon: '—',  cost: 0,    desc: 'Bez optymalizacji – odpowiednie dla projektów wewnętrznych lub intranetów' },
        basic:    { label: 'SEO podstawowe',   icon: '🔍', cost: 600,  desc: 'Tagi meta, sitemap XML, schema.org i optymalizacja prędkości ładowania' },
        advanced: { label: 'SEO zaawansowane', icon: '🚀', cost: 1800, desc: 'Audyt słów kluczowych, linkowanie wewnętrzne i strategia content marketingowa' },
    },
    deadline: {
        standard: { label: 'Standardowy (6–8 tyg.)',   icon: '📅', multiplier: 1.0, desc: 'Optymalny przebieg projektu z czasem na testy i Twój feedback' },
        fast:     { label: 'Przyspieszony (3–4 tyg.)', icon: '⚡', multiplier: 1.3, desc: 'Wyższy priorytet i praca w intensywnym trybie – wymaga szybkiego feedbacku' },
        urgent:   { label: 'Pilny (1–2 tyg.)',         icon: '🔥', multiplier: 1.6, desc: 'Pełna mobilizacja zespołu 24/7 – decyzje muszą być podejmowane na bieżąco' },
    },
    hosting: {
        none:  { label: 'Własny hosting',         icon: '🏠', cost: 0,  desc: 'Masz już własny serwer lub korzystasz z dostawcy chmury (AWS, GCP itp.)' },
        basic: { label: 'Hosting Basic (£4/mo.)', icon: '💾', cost: 48, desc: 'SSD, PHP 8.x, certyfikat SSL, 10 GB przestrzeni – dla małych i średnich stron' },
        pro:   { label: 'Hosting Pro (£8/mo.)',   icon: '🖥️', cost: 96, desc: 'Wydajny VPS z codziennym backupem, monitoringiem uptime i dedykowanym IP' },
    },
};

const TOTAL_STEPS = 8;

function calcEstimate(a) {
    const pt  = PRICING.projectType[a.projectType];
    const des = PRICING.design[a.design];
    const cms = PRICING.cms[a.cms];
    const seo = PRICING.seoPackage[a.seoPackage];
    const dl  = PRICING.deadline[a.deadline];
    const ho  = PRICING.hosting[a.hosting];
    if (!pt || !des || !cms || !seo || !dl || !ho) return null;
    const pagesAddon = Math.max(0, (a.pages - 5)) * 80;
    const intTotal   = (a.integrations || []).reduce((s, k) => s + (PRICING.integrations[k]?.cost || 0), 0);
    const base  = (pt.base + pagesAddon) * des.multiplier + cms.cost + intTotal + seo.cost;
    const total = base * dl.multiplier + ho.cost;
    return { low: Math.round(total * 0.9 / 100) * 100, high: Math.round(total * 1.15 / 100) * 100 };
}

function ProgressBar({ step }) {
    return (
        <div className="mb-6">
            <div className="flex items-center justify-between mb-1">
                <span className="text-xs font-semibold uppercase tracking-widest text-brand-500">Krok {step} z {TOTAL_STEPS}</span>
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

function NavBtns({ onBack, onNext, canNext, nextLabel = 'Dalej →' }) {
    return (
        <div className="flex gap-3 mt-8">
            {onBack && (
                <button
                    type="button"
                    onClick={onBack}
                    className="px-5 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:border-brand-500 hover:text-brand-500 transition-colors"
                >
                    ← Wstecz
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

export default function CostCalculator() {
    const [step, setStep] = useState(1);
    const [a, setA]       = useState({ projectType: '', pages: 5, design: '', cms: '', integrations: [], seoPackage: '', deadline: '', hosting: '', companyName: '', contactEmail: '' });
    const [submitted, setSubmitted] = useState(false);

    const set      = useCallback((k, v) => setA(prev => ({ ...prev, [k]: v })), []);
    const toggleInt = useCallback((k) => setA(prev => {
        const list = prev.integrations.includes(k) ? prev.integrations.filter(x => x !== k) : [...prev.integrations, k];
        return { ...prev, integrations: list };
    }), []);

    const next = () => setStep(s => s + 1);
    const back = () => setStep(s => s - 1);
    const estimate = calcEstimate(a);

    // Result screen
    if (step > TOTAL_STEPS && estimate) {
        return (
            <div className="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-6 sm:p-8 text-center max-w-2xl mx-auto">
                <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-brand-500/10">
                    <span className="text-3xl">🎯</span>
                </div>
                <h3 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-2">Twoja szacowana wycena</h3>
                <p className="text-neutral-500 dark:text-neutral-400 text-sm mb-6">Wycena orientacyjna na podstawie podanych informacji.</p>

                <div className="bg-white dark:bg-neutral-950 rounded-xl p-6 mb-6 border border-neutral-100 dark:border-neutral-700">
                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-1">Szacowany koszt projektu</p>
                    <p className="font-display text-4xl font-extrabold text-brand-500">{fmt(estimate.low)} – {fmt(estimate.high)}</p>
                    {a.hosting !== 'none' && (
                        <p className="text-xs text-neutral-400 mt-2">+ hosting {fmt(PRICING.hosting[a.hosting].cost)}/year</p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2 justify-center mb-6">
                    {a.projectType && <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-brand-500/10 text-brand-500">{PRICING.projectType[a.projectType].label}</span>}
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">{a.pages} podstron</span>
                    {a.integrations.length > 0 && <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">{a.integrations.length} integracje</span>}
                    {a.deadline && <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">{PRICING.deadline[a.deadline].label}</span>}
                </div>

                {!submitted ? (
                    <div>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-4">Podaj swoje dane, żebyśmy mogli wysłać Ci szczegółową ofertę.</p>
                        <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-4">
                            <input type="text" placeholder="Twoje imię / firma" value={a.companyName} onChange={e => set('companyName', e.target.value)} className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" />
                            <input type="email" placeholder="Twój email" value={a.contactEmail} onChange={e => set('contactEmail', e.target.value)} className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" />
                        </div>
                        <button
                            type="button"
                            disabled={!a.contactEmail}
                            onClick={() => { if (a.contactEmail) setSubmitted(true); }}
                            className={`inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-sm transition-all ${a.contactEmail ? 'bg-brand-500 text-white hover:opacity-90 active:scale-95' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}
                        >
                            Wyślij zapytanie 🚀
                        </button>
                    </div>
                ) : (
                    <div className="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800">
                        <p className="text-green-700 dark:text-green-400 font-semibold text-sm">✓ Gotowe! Odezwiemy się w ciągu 24h roboczych.</p>
                        <p className="text-green-600 dark:text-green-500 text-xs mt-1">Na adres: {a.contactEmail}</p>
                    </div>
                )}

                <button
                    type="button"
                    onClick={() => { setStep(1); setA({ projectType: '', pages: 5, design: '', cms: '', integrations: [], seoPackage: '', deadline: '', hosting: '', companyName: '', contactEmail: '' }); setSubmitted(false); }}
                    className="mt-4 text-xs text-neutral-400 hover:text-brand-500 transition-colors underline underline-offset-2"
                >
                    Zacznij od nowa
                </button>
            </div>
        );
    }

    const steps = {
        1: (
            <>
                <ProgressBar step={1} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Jakiego projektu potrzebujesz?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Wybierz typ projektu, który najlepiej opisuje Twoje potrzeby.</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(PRICING.projectType).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.projectType === k} onClick={val => set('projectType', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={`od ${fmt(v.base)}`} />)}
                </div>
                <NavBtns onNext={next} canNext={!!a.projectType} />
            </>
        ),
        2: (
            <>
                <ProgressBar step={2} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Ile podstron?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Np. Strona główna, O nas, Usługi, Blog, Kontakt.</p>
                <div className="flex flex-col items-center gap-4 py-4">
                    <span className="font-display text-5xl font-bold text-brand-500" aria-live="polite">{a.pages}</span>
                    <input type="range" min={1} max={50} step={1} value={a.pages} onChange={e => set('pages', Number(e.target.value))} className="w-full max-w-xs" aria-label="Liczba podstron" />
                    <div className="flex justify-between w-full max-w-xs text-xs text-neutral-400">
                        <span>1</span><span>10</span><span>25</span><span>50+</span>
                    </div>
                    {a.pages > 5 && <p className="text-xs text-neutral-500">Każda strona powyżej 5: +£80</p>}
                </div>
                <NavBtns onBack={back} onNext={next} canNext />
            </>
        ),
        3: (
            <>
                <ProgressBar step={3} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Jaki poziom designu?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Custom design to indywidualny projekt graficzny od zera.</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING.design).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.design === k} onClick={val => set('design', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={v.multiplier > 1.0 ? `×${v.multiplier.toFixed(1)} ceny bazowej` : undefined} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.design} />
            </>
        ),
        4: (
            <>
                <ProgressBar step={4} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Czy potrzebujesz systemu CMS?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">CMS pozwala samodzielnie edytować treści bez programisty.</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING.cms).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.cms === k} onClick={val => set('cms', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : 'bez dopłaty'} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.cms} />
            </>
        ),
        5: (
            <>
                <ProgressBar step={5} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Jakie integracje?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Możesz wybrać wiele opcji lub pominąć ten krok.</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(PRICING.integrations).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.integrations.includes(k)} onClick={() => toggleInt(k)} icon={v.icon} label={v.label} desc={v.desc} sublabel={`+${fmt(v.cost)}`} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext nextLabel={a.integrations.length > 0 ? 'Dalej →' : 'Pomiń →'} />
            </>
        ),
        6: (
            <>
                <ProgressBar step={6} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Pakiet SEO?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Optymalizacja pod wyszukiwarki – więcej ruchu organicznego.</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING.seoPackage).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.seoPackage === k} onClick={val => set('seoPackage', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : 'bez dopłaty'} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.seoPackage} />
            </>
        ),
        7: (
            <>
                <ProgressBar step={7} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Kiedy potrzebujesz projektu?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Szybsze terminy wymagają dodatkowych zasobów.</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING.deadline).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.deadline === k} onClick={val => set('deadline', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={v.multiplier > 1 ? `+${Math.round((v.multiplier - 1) * 100)}% do wyceny` : 'standardowa wycena'} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.deadline} />
            </>
        ),
        8: (
            <>
                <ProgressBar step={8} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">Hosting i utrzymanie?</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Ceny hostingu podane jako dopłata roczna.</p>
                <div className="grid gap-2">
                    {Object.entries(PRICING.hosting).map(([k, v]) => <OptionBtn key={k} value={k} selected={a.hosting === k} onClick={val => set('hosting', val)} icon={v.icon} label={v.label} desc={v.desc} sublabel={v.cost > 0 ? `${fmt(v.cost)}/year` : 'self-managed'} />)}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.hosting} nextLabel="Oblicz wycenę 🚀" />
            </>
        ),
    };

    return (
        <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 shadow-sm" role="form" aria-label="Kalkulator kosztów projektu">
            {steps[step] || null}
        </div>
    );
}
