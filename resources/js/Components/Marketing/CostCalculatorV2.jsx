import { useState, useCallback } from 'react';
import { pushEvent } from '@/utils/dataLayer';

/**
 * CostCalculatorV2
 *
 * 100% DB-driven — zero hardcoded strings.
 * All text comes from:
 *   strings  — object { key: 'pre-localized value' }   (from calculator_strings table)
 *   steps    — array  [{ question, hint }, …]           (from calculator_steps table)
 *   pricing  — object { projectType, design, … }        (from calculator_pricing table)
 *
 * The controller resolves the locale and passes already-localized values,
 * so this component does NOT import usePage() for text purposes.
 */

const fmt = v =>
    new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP', minimumFractionDigits: 0 }).format(v);

function calcEstimate(a, P) {
    if (!P) return null;
    const pt  = P.projectType?.[a.projectType];
    const des = P.design?.[a.design];
    const cms = P.cms?.[a.cms];
    const seo = P.seoPackage?.[a.seoPackage];
    const dl  = P.deadline?.[a.deadline];
    const ho  = P.hosting?.[a.hosting];
    if (!pt || !des || !cms || !seo || !dl || !ho) return null;
    const pagesAddon = Math.max(0, (a.pages - 5)) * 80;
    const intTotal   = (a.integrations || []).reduce((sum, k) => sum + (P.integrations?.[k]?.cost || 0), 0);
    const base  = (pt.base + pagesAddon) * des.multiplier + cms.cost + intTotal + seo.cost;
    const total = base * dl.multiplier + ho.cost;
    return { low: Math.round(total * 0.9 / 100) * 100, high: Math.round(total * 1.15 / 100) * 100 };
}

// ---------------------------------------------------------------------------
// Helper: read pre-localized string (fallback to key if missing)
// ---------------------------------------------------------------------------
function makeS(strings) {
    return (key, fallback = '') => strings?.[key] ?? fallback;
}

// ---------------------------------------------------------------------------
// Helper: read localized label/desc from a pricing entry
// The pricing entry has label_en, label_pl, label_pt keys; controller already
// resolved the locale, so the entry has all three — we pick the matching locale
// via a special `_loc` sentinel the controller injects.
// ---------------------------------------------------------------------------
function tPricing(entry, field) {
    // Controller injects `_locale` into each pricing group so V2 can pick it
    if (!entry) return '';
    return entry[`${field}_loc`] ?? entry[`${field}_en`] ?? entry[field] ?? '';
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function ProgressBar({ step, total, stepLabel, ofLabel }) {
    return (
        <div className="mb-6">
            <div className="flex items-center justify-between mb-1">
                <span className="text-xs font-semibold uppercase tracking-widest text-brand-500">
                    {stepLabel} {step} {ofLabel} {total}
                </span>
                <span className="text-xs text-neutral-400">{Math.round(step / total * 100)}%</span>
            </div>
            <div className="w-full h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800">
                <div
                    className="h-1.5 rounded-full transition-all duration-500 bg-brand-500"
                    style={{ width: `${step / total * 100}%` }}
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
                {desc     && <span className="text-xs text-neutral-500 dark:text-neutral-400 font-normal mt-0.5 leading-relaxed">{desc}</span>}
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

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CostCalculatorV2({ strings: rawStrings = {}, steps = [], pricing = null }) {
    const s = makeS(rawStrings);

    const TOTAL_STEPS = steps.length || 8;

    const [step, setStep]           = useState(1);
    const [a, setA]                 = useState({
        projectType: '', pages: 5, design: '', cms: '',
        integrations: [], seoPackage: '', deadline: '', hosting: '',
        companyName: '', contactEmail: '',
    });
    const [submitted,  setSubmitted]  = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const set       = useCallback((k, v) => setA(prev => ({ ...prev, [k]: v })), []);
    const toggleInt = useCallback((k)    => setA(prev => {
        const list = prev.integrations.includes(k)
            ? prev.integrations.filter(x => x !== k)
            : [...prev.integrations, k];
        return { ...prev, integrations: list };
    }), []);

    const next  = () => setStep(s => s + 1);
    const back  = () => setStep(s => s - 1);
    const reset = () => {
        setStep(1);
        setA({ projectType: '', pages: 5, design: '', cms: '', integrations: [], seoPackage: '', deadline: '', hosting: '', companyName: '', contactEmail: '' });
        setSubmitted(false);
    };

    const estimate = calcEstimate(a, pricing);

    const handleSubmit = useCallback(async () => {
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
            // non-blocking
        } finally {
            setSubmitting(false);
            pushEvent('generate_lead', {
                lead_source:  'calculator_v2',
                project_type: a.projectType,
                estimate_low:  estimate?.low,
                estimate_high: estimate?.high,
            });
            if (typeof window.fbq === 'function') window.fbq('track', 'Lead');
            setSubmitted(true);
        }
    }, [a, estimate, submitting]);

    // -----------------------------------------------------------------------
    // Pricing label helpers (entries already contain label_en, label_pl, label_pt)
    // The controller also injected _locale suffix to a special "_loc" key
    // so tPricing() picks the right locale.
    // -----------------------------------------------------------------------
    const entryLabel = (entry) => tPricing(entry, 'label');
    const entryDesc  = (entry) => tPricing(entry, 'desc');

    // Derived sublabels — all text from strings{}
    const fromLabel              = s('from_label',            'from');
    const baseMultiplierLabel    = s('base_multiplier_label', 'of base price');
    const noExtraLabel           = s('no_extra_label',        'no extra charge');
    const toQuoteLabel           = s('to_quote_label',        'to quote');
    const standardPricingLabel   = s('standard_pricing_label','standard pricing');
    const perYearLabel           = s('per_year',              '/year');
    const selfManagedLabel       = s('self_managed',          'self-managed');

    // Step question / hint helpers
    const sq = (i) => steps[i]?.question ?? '';
    const sh = (i) => steps[i]?.hint     ?? '';

    // -----------------------------------------------------------------------
    // Result screen
    // -----------------------------------------------------------------------
    if (step > TOTAL_STEPS && estimate) {
        return (
            <div className="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-6 sm:p-8 text-center max-w-2xl mx-auto">
                <div className="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-brand-500/10">
                    <span className="text-3xl">🎯</span>
                </div>
                <h3 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-2">
                    {s('result_title', 'Your Estimated Quote')}
                </h3>
                <p className="text-neutral-500 dark:text-neutral-400 text-sm mb-6">
                    {s('result_subtitle', 'Estimate based on the information you provided.')}
                </p>

                <div className="bg-white dark:bg-neutral-950 rounded-xl p-6 mb-6 border border-neutral-100 dark:border-neutral-700">
                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-1">
                        {s('result_cost_label', 'Estimated project cost')}
                    </p>
                    <p className="font-display text-4xl font-extrabold text-brand-500">
                        {fmt(estimate.low)} – {fmt(estimate.high)}
                    </p>
                    {a.hosting !== 'none' && pricing?.hosting?.[a.hosting] && (
                        <p className="text-xs text-neutral-400 mt-2">
                            {s('hosting_addon_label', '+ hosting')} {fmt(pricing.hosting[a.hosting].cost)}{perYearLabel}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2 justify-center mb-6">
                    {a.projectType && pricing?.projectType?.[a.projectType] && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-brand-500/10 text-brand-500">
                            {entryLabel(pricing.projectType[a.projectType])}
                        </span>
                    )}
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                        {a.pages} {s('pages_chip', 'pages')}
                    </span>
                    {a.integrations.length > 0 && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                            {a.integrations.length} {s('integrations_chip', 'integrations')}
                        </span>
                    )}
                    {a.deadline && pricing?.deadline?.[a.deadline] && (
                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300">
                            {entryLabel(pricing.deadline[a.deadline])}
                        </span>
                    )}
                </div>

                {!submitted ? (
                    <div>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                            {s('contact_title', "Enter your details and we'll send you a detailed quote.")}
                        </p>
                        <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-4">
                            <input
                                type="text"
                                placeholder={s('name_placeholder', 'Your name / company')}
                                value={a.companyName}
                                onChange={e => set('companyName', e.target.value)}
                                className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                            <input
                                type="email"
                                placeholder={s('email_placeholder', 'Your email')}
                                value={a.contactEmail}
                                onChange={e => set('contactEmail', e.target.value)}
                                className="flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                        </div>
                        <button
                            type="button"
                            disabled={!a.contactEmail || submitting}
                            onClick={handleSubmit}
                            className={`inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-sm transition-all ${a.contactEmail && !submitting ? 'bg-brand-500 text-white hover:opacity-90 active:scale-95' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}
                        >
                            {submitting ? s('submitting_btn', 'Sending…') : s('submit_btn', 'Send enquiry 🚀')}
                        </button>
                    </div>
                ) : (
                    <div className="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800">
                        <p className="text-green-700 dark:text-green-400 font-semibold text-sm">
                            {s('success_msg', "✓ Done! We'll get back to you within 1 business day.")}
                        </p>
                        <p className="text-green-600 dark:text-green-500 text-xs mt-1">
                            {s('sent_to', 'Sent to:')} {a.contactEmail}
                        </p>
                    </div>
                )}

                <button
                    type="button"
                    onClick={reset}
                    className="mt-4 text-xs text-neutral-400 hover:text-brand-500 transition-colors underline underline-offset-2"
                >
                    {s('restart', 'Start over')}
                </button>
            </div>
        );
    }

    // -----------------------------------------------------------------------
    // Step screens
    // -----------------------------------------------------------------------
    const stepLabel = s('step_label', 'Step');
    const ofLabel   = s('step_of',   'of');
    const backLabel = s('nav_back',  '← Back');
    const nextLabel = s('nav_next',  'Next →');
    const skipLabel = s('nav_skip',  'Skip →');
    const calcLabel = s('nav_calc',  'Calculate Quote 🚀');

    const stepScreens = {
        1: pricing?.projectType && (
            <>
                <ProgressBar step={1} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(0)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(0)}</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(pricing.projectType).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.projectType === k}
                            onClick={val => set('projectType', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={`${fromLabel} ${fmt(v.base)}`}
                        />
                    ))}
                </div>
                <NavBtns onNext={next} canNext={!!a.projectType} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        2: (
            <>
                <ProgressBar step={2} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(1)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(1)}</p>
                <div className="flex flex-col items-center gap-4 py-4">
                    <span className="font-display text-5xl font-bold text-brand-500" aria-live="polite">{a.pages}</span>
                    <input type="range" min={1} max={50} step={1} value={a.pages}
                        onChange={e => set('pages', Number(e.target.value))}
                        className="w-full max-w-xs"
                        aria-label={sq(1)} />
                    <div className="flex justify-between w-full max-w-xs text-xs text-neutral-400">
                        <span>1</span><span>10</span><span>25</span><span>50+</span>
                    </div>
                    {a.pages > 5 && (
                        <p className="text-xs text-neutral-500">{s('pages_addon', 'Each page above 5: +£80')}</p>
                    )}
                </div>
                <NavBtns onBack={back} onNext={next} canNext nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        3: pricing?.design && (
            <>
                <ProgressBar step={3} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(2)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(2)}</p>
                <div className="grid gap-2">
                    {Object.entries(pricing.design).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.design === k}
                            onClick={val => set('design', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={v.multiplier > 1.0
                                ? `×${v.multiplier.toFixed(1)} ${baseMultiplierLabel}`
                                : undefined}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.design} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        4: pricing?.cms && (
            <>
                <ProgressBar step={4} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(3)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(3)}</p>
                <div className="grid gap-2">
                    {Object.entries(pricing.cms).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.cms === k}
                            onClick={val => set('cms', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : noExtraLabel}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.cms} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        5: pricing?.integrations && (
            <>
                <ProgressBar step={5} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(4)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(4)}</p>
                <div className="grid sm:grid-cols-2 gap-2">
                    {Object.entries(pricing.integrations).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.integrations.includes(k)}
                            onClick={() => toggleInt(k)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={`+${fmt(v.cost)}`}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext
                    nextLabel={a.integrations.length > 0 ? nextLabel : skipLabel}
                    backLabel={backLabel}
                />
            </>
        ),

        6: pricing?.seoPackage && (
            <>
                <ProgressBar step={6} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(5)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(5)}</p>
                <div className="grid gap-2">
                    {Object.entries(pricing.seoPackage).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.seoPackage === k}
                            onClick={val => set('seoPackage', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={v.cost > 0 ? `+${fmt(v.cost)}` : noExtraLabel}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.seoPackage} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        7: pricing?.deadline && (
            <>
                <ProgressBar step={7} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(6)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(6)}</p>
                <div className="grid gap-2">
                    {Object.entries(pricing.deadline).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.deadline === k}
                            onClick={val => set('deadline', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={v.multiplier > 1
                                ? `+${Math.round((v.multiplier - 1) * 100)}% ${toQuoteLabel}`
                                : standardPricingLabel}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.deadline} nextLabel={nextLabel} backLabel={backLabel} />
            </>
        ),

        8: pricing?.hosting && (
            <>
                <ProgressBar step={8} total={TOTAL_STEPS} stepLabel={stepLabel} ofLabel={ofLabel} />
                <h3 className="font-display text-xl font-bold text-neutral-900 dark:text-white mb-1">{sq(7)}</h3>
                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-5">{sh(7)}</p>
                <div className="grid gap-2">
                    {Object.entries(pricing.hosting).map(([k, v]) => (
                        <OptionBtn key={k} value={k}
                            selected={a.hosting === k}
                            onClick={val => set('hosting', val)}
                            icon={v.icon}
                            label={entryLabel(v)}
                            desc={entryDesc(v)}
                            sublabel={v.cost > 0
                                ? `${fmt(v.cost)}${perYearLabel}`
                                : selfManagedLabel}
                        />
                    ))}
                </div>
                <NavBtns onBack={back} onNext={next} canNext={!!a.hosting} nextLabel={calcLabel} backLabel={backLabel} />
            </>
        ),
    };

    return (
        <section id="kalkulator-v2" className="py-20 md:py-28 bg-neutral-50 dark:bg-neutral-950">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-12 reveal">
                    <span className="section-label">{s('section_label', 'Cost Calculator')}</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {s('title', 'How Much Will Your Project Cost?')}
                    </h2>
                    <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                        {s('subtitle', 'Answer a few questions and get an instant quote estimate.')}
                    </p>
                </div>

                <div
                    className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 shadow-sm reveal"
                    role="form"
                    aria-label={s('section_label', 'Cost Calculator')}
                >
                    {stepScreens[step] || null}
                </div>
            </div>
        </section>
    );
}
