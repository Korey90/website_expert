// CostCalculator.jsx – WebsiteExpert (CDN React + Babel, prototyp)
// Mount: <div id="cost-calculator-root"></div>

const { useState, useCallback } = React;

// ── Pricing config ────────────────────────────────────────────────────────────
const PRICING = {
  projectType: {
    wizytowka:   { label: 'Strona wizytówkowa',    icon: '🌐', base: 2500 },
    landing:     { label: 'Landing page',           icon: '🎯', base: 1500 },
    ecommerce:   { label: 'Sklep e-commerce',       icon: '🛒', base: 6000 },
    aplikacja:   { label: 'Aplikacja webowa',       icon: '⚙️',  base: 12000 },
    blog:        { label: 'Blog / Portal',          icon: '📰', base: 3500 },
  },
  design: {
    template:  { label: 'Gotowy szablon',    icon: '📋', multiplier: 1.0 },
    custom:    { label: 'Design custom',     icon: '🎨', multiplier: 1.5 },
    premium:   { label: 'Design premium UI/UX', icon: '✨', multiplier: 2.1 },
  },
  cms: {
    none:      { label: 'Brak CMS',          icon: '❌', cost: 0 },
    basic:     { label: 'Prosty CMS',        icon: '🗂️', cost: 800 },
    advanced:  { label: 'Zaawansowany CMS',  icon: '💡', cost: 2000 },
  },
  integrations: {
    payment:   { label: 'Płatności online',  icon: '💳', cost: 900 },
    api:       { label: 'Integracje API',    icon: '🔗', cost: 1200 },
    crm:       { label: 'CRM / ERP',        icon: '📊', cost: 1500 },
    newsletter:{ label: 'Newsletter / Mailing', icon: '📧', cost: 500 },
    analytics: { label: 'Analytics (GA4/Pixel)', icon: '📈', cost: 300 },
  },
  seoPackage: {
    none:      { label: 'Bez SEO',           icon: '—',  cost: 0 },
    basic:     { label: 'SEO podstawowe',    icon: '🔍', cost: 600 },
    advanced:  { label: 'SEO zaawansowane',  icon: '🚀', cost: 1800 },
  },
  deadline: {
    standard:  { label: 'Standardowy (6–8 tyg.)', icon: '📅', multiplier: 1.0 },
    fast:      { label: 'Przyspieszony (3–4 tyg.)', icon: '⚡', multiplier: 1.3 },
    urgent:    { label: 'Pilny (1–2 tyg.)',       icon: '🔥', multiplier: 1.6 },
  },
  hosting: {
    none:      { label: 'Własny hosting',    icon: '🏠', cost: 0 },
    basic:     { label: 'Hosting Basic (39 zł/mies.)',    icon: '💾', cost: 468 },
    pro:       { label: 'Hosting Pro (79 zł/mies.)',      icon: '🖥️', cost: 948 },
  },
};

// ── Helpers ───────────────────────────────────────────────────────────────────
function formatPLN(value) {
  return new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN', minimumFractionDigits: 0 }).format(value);
}

function calcEstimate(answers) {
  const pt  = PRICING.projectType[answers.projectType];
  const des = PRICING.design[answers.design];
  const cms = PRICING.cms[answers.cms];
  const seo = PRICING.seoPackage[answers.seoPackage];
  const dl  = PRICING.deadline[answers.deadline];
  const ho  = PRICING.hosting[answers.hosting];
  if (!pt || !des || !cms || !seo || !dl || !ho) return null;

  const pagesAddon = Math.max(0, (answers.pages - 5)) * 200;

  const integrationsTotal = (answers.integrations || []).reduce((sum, key) => {
    return sum + (PRICING.integrations[key]?.cost || 0);
  }, 0);

  const base = (pt.base + pagesAddon) * des.multiplier + cms.cost + integrationsTotal + seo.cost;
  const total = (base * dl.multiplier) + ho.cost;

  return {
    low: Math.round(total * 0.9 / 100) * 100,
    high: Math.round(total * 1.15 / 100) * 100,
  };
}

// ── Reusable sub-components ───────────────────────────────────────────────────
function StepHeader({ step, total, title, desc }) {
  return (
    React.createElement('div', { className: 'mb-6' },
      React.createElement('div', { className: 'flex items-center justify-between mb-1' },
        React.createElement('span', { className: 'text-xs font-semibold text-brand-500 uppercase tracking-widest' },
          `Krok ${step} z ${total}`
        ),
        React.createElement('span', { className: 'text-xs text-neutral-400' },
          `${Math.round((step / total) * 100)}%`
        )
      ),
      React.createElement('div', { className: 'w-full h-1.5 rounded-full bg-neutral-100 dark:bg-neutral-800 mb-4' },
        React.createElement('div', {
          className: 'h-1.5 rounded-full bg-brand-500 transition-all duration-500',
          style: { width: `${(step / total) * 100}%` }
        })
      ),
      React.createElement('h3', { className: 'font-display text-xl font-bold text-neutral-900 dark:text-white' }, title),
      desc && React.createElement('p', { className: 'mt-1 text-sm text-neutral-500 dark:text-neutral-400' }, desc)
    )
  );
}

function OptionBtn({ value, selected, onClick, icon, label, sublabel }) {
  return (
    React.createElement('button', {
      type: 'button',
      onClick: () => onClick(value),
      className: `calc-option-btn${selected ? ' selected' : ''}`,
      'aria-pressed': selected,
    },
      React.createElement('span', { className: 'text-xl w-7 text-center shrink-0', 'aria-hidden': 'true' }, icon),
      React.createElement('span', { className: 'flex flex-col' },
        React.createElement('span', null, label),
        sublabel && React.createElement('span', { className: 'text-xs text-neutral-400 dark:text-neutral-500 font-normal' }, sublabel)
      )
    )
  );
}

function NavButtons({ onBack, onNext, canNext, nextLabel = 'Dalej →', isLast = false }) {
  return (
    React.createElement('div', { className: 'flex gap-3 mt-8' },
      onBack && React.createElement('button', {
        type: 'button',
        onClick: onBack,
        className: 'flex-1 sm:flex-none px-5 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:border-brand-500 hover:text-brand-500 transition-colors'
      }, '← Wstecz'),
      React.createElement('button', {
        type: 'button',
        onClick: onNext,
        disabled: !canNext,
        className: `flex-1 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all ${
          canNext
            ? 'bg-brand-500 text-white hover:bg-brand-600 active:scale-95 shadow-md shadow-brand-500/20'
            : 'bg-neutral-200 dark:bg-neutral-800 text-neutral-400 cursor-not-allowed'
        } ${isLast ? 'sm:flex-none sm:px-8' : ''}`
      }, nextLabel)
    )
  );
}

// ── Main Calculator component ─────────────────────────────────────────────────
function CostCalculator() {
  const TOTAL_STEPS = 8;

  const [step, setStep] = useState(1);
  const [answers, setAnswers] = useState({
    projectType: '',
    pages: 5,
    design: '',
    cms: '',
    integrations: [],
    seoPackage: '',
    deadline: '',
    hosting: '',
    companyName: '',
    contactEmail: '',
  });
  const [submitted, setSubmitted] = useState(false);

  const set = useCallback((key, value) => {
    setAnswers(prev => ({ ...prev, [key]: value }));
  }, []);

  const toggleIntegration = useCallback((key) => {
    setAnswers(prev => {
      const list = prev.integrations.includes(key)
        ? prev.integrations.filter(k => k !== key)
        : [...prev.integrations, key];
      return { ...prev, integrations: list };
    });
  }, []);

  const next = () => setStep(s => Math.min(s + 1, TOTAL_STEPS));
  const back = () => setStep(s => Math.max(s - 1, 1));

  const estimate = calcEstimate(answers);

  // ── Step renderers ──────────────────────────────────────────────────────────
  function renderStep() {
    switch (step) {
      // STEP 1 – Project type
      case 1: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 1, total: TOTAL_STEPS, title: 'Jakiego projektu potrzebujesz?', desc: 'Wybierz typ projektu, który najlepiej opisuje Twoje potrzeby.' }),
          React.createElement('div', { className: 'grid sm:grid-cols-2 gap-2' },
            Object.entries(PRICING.projectType).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.projectType === key,
                onClick: (v) => set('projectType', v),
                icon: val.icon, label: val.label,
                sublabel: `od ${formatPLN(val.base)}`
              })
            )
          ),
          React.createElement(NavButtons, { onNext: next, canNext: !!answers.projectType })
        )
      );

      // STEP 2 – Pages count
      case 2: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 2, total: TOTAL_STEPS, title: 'Ile podstron?', desc: 'Podstronami są np. Strona główna, O nas, Usługi, Blog, Kontakt.' }),
          React.createElement('div', { className: 'flex flex-col items-center gap-4 py-4' },
            React.createElement('span', { className: 'font-display text-5xl font-bold text-brand-500', 'aria-live': 'polite' }, answers.pages),
            React.createElement('input', {
              type: 'range', min: 1, max: 50, step: 1,
              value: answers.pages,
              onChange: e => set('pages', Number(e.target.value)),
              className: 'w-full max-w-xs',
              'aria-label': 'Liczba podstron'
            }),
            React.createElement('div', { className: 'flex justify-between w-full max-w-xs text-xs text-neutral-400' },
              React.createElement('span', null, '1'),
              React.createElement('span', null, '10'),
              React.createElement('span', null, '25'),
              React.createElement('span', null, '50+')
            ),
            answers.pages > 5 && React.createElement('p', { className: 'text-xs text-neutral-500 dark:text-neutral-400' },
              `Każda dodatkowa podstrona powyżej 5: +200 zł`
            )
          ),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: true })
        )
      );

      // STEP 3 – Design
      case 3: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 3, total: TOTAL_STEPS, title: 'Jaki poziom designu?', desc: 'Custom design to indywidualny projekt graficzny od zera.' }),
          React.createElement('div', { className: 'grid gap-2' },
            Object.entries(PRICING.design).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.design === key,
                onClick: (v) => set('design', v),
                icon: val.icon, label: val.label,
              })
            )
          ),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: !!answers.design })
        )
      );

      // STEP 4 – CMS
      case 4: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 4, total: TOTAL_STEPS, title: 'Czy potrzebujesz systemu CMS?', desc: 'CMS pozwala samodzielnie edytować treści bez programisty.' }),
          React.createElement('div', { className: 'grid gap-2' },
            Object.entries(PRICING.cms).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.cms === key,
                onClick: (v) => set('cms', v),
                icon: val.icon, label: val.label,
                sublabel: val.cost > 0 ? `+${formatPLN(val.cost)}` : 'bez dopłaty'
              })
            )
          ),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: !!answers.cms })
        )
      );

      // STEP 5 – Integrations
      case 5: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 5, total: TOTAL_STEPS, title: 'Jakie integracje?', desc: 'Możesz wybrać wiele opcji.' }),
          React.createElement('div', { className: 'grid sm:grid-cols-2 gap-2' },
            Object.entries(PRICING.integrations).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.integrations.includes(key),
                onClick: () => toggleIntegration(key),
                icon: val.icon, label: val.label,
                sublabel: `+${formatPLN(val.cost)}`
              })
            )
          ),
          React.createElement('p', { className: 'mt-3 text-xs text-neutral-400' }, 'Możesz pominąć ten krok jeśli nie potrzebujesz integracji.'),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: true, nextLabel: answers.integrations.length > 0 ? 'Dalej →' : 'Pomiń →' })
        )
      );

      // STEP 6 – SEO
      case 6: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 6, total: TOTAL_STEPS, title: 'Pakiet SEO?', desc: 'Optymalizacja pod wyszukiwarki - więcej organicznego ruchu.' }),
          React.createElement('div', { className: 'grid gap-2' },
            Object.entries(PRICING.seoPackage).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.seoPackage === key,
                onClick: (v) => set('seoPackage', v),
                icon: val.icon, label: val.label,
                sublabel: val.cost > 0 ? `+${formatPLN(val.cost)}` : 'bez dopłaty'
              })
            )
          ),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: !!answers.seoPackage })
        )
      );

      // STEP 7 – Deadline
      case 7: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 7, total: TOTAL_STEPS, title: 'Kiedy potrzebujesz projektu?', desc: 'Szybsze terminy wymagają dodatkowych zasobów.' }),
          React.createElement('div', { className: 'grid gap-2' },
            Object.entries(PRICING.deadline).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.deadline === key,
                onClick: (v) => set('deadline', v),
                icon: val.icon, label: val.label,
                sublabel: val.multiplier > 1 ? `+${Math.round((val.multiplier - 1) * 100)}% do wyceny` : 'standardowa wycena'
              })
            )
          ),
          React.createElement(NavButtons, { onBack: back, onNext: next, canNext: !!answers.deadline })
        )
      );

      // STEP 8 – Hosting
      case 8: return (
        React.createElement('div', null,
          React.createElement(StepHeader, { step: 8, total: TOTAL_STEPS, title: 'Hosting i utrzymanie?', desc: 'Ceny hostingu podane jako dopłata roczna.' }),
          React.createElement('div', { className: 'grid gap-2' },
            Object.entries(PRICING.hosting).map(([key, val]) =>
              React.createElement(OptionBtn, {
                key, value: key,
                selected: answers.hosting === key,
                onClick: (v) => set('hosting', v),
                icon: val.icon, label: val.label,
                sublabel: val.cost > 0 ? `${formatPLN(val.cost)}/rok` : 'we własnym zakresie'
              })
            )
          ),
          React.createElement(NavButtons, {
            onBack: back, onNext: next,
            canNext: !!answers.hosting,
            nextLabel: 'Oblicz wycenę 🚀',
            isLast: true
          })
        )
      );

      default: return null;
    }
  }

  // ── Result screen ───────────────────────────────────────────────────────────
  if (step > TOTAL_STEPS && estimate) {
    return React.createElement('div', { className: 'rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 p-6 sm:p-8 text-center max-w-2xl mx-auto' },
      React.createElement('div', { className: 'w-16 h-16 rounded-2xl bg-brand-500/10 flex items-center justify-center mx-auto mb-4' },
        React.createElement('span', { className: 'text-3xl', 'aria-hidden': 'true' }, '🎯')
      ),
      React.createElement('h3', { className: 'font-display text-2xl font-bold text-neutral-900 dark:text-white mb-2' }, 'Twoja szacowana wycena'),
      React.createElement('p', { className: 'text-neutral-500 dark:text-neutral-400 text-sm mb-6' }, 'Wycena orientacyjna na podstawie podanych informacji.'),

      React.createElement('div', { className: 'bg-white dark:bg-neutral-950 rounded-xl p-6 mb-6 border border-neutral-100 dark:border-neutral-700' },
        React.createElement('p', { className: 'text-sm text-neutral-500 dark:text-neutral-400 mb-1' }, 'Szacowany koszt projektu'),
        React.createElement('p', { className: 'font-display text-4xl font-extrabold text-brand-500' },
          `${formatPLN(estimate.low)} – ${formatPLN(estimate.high)}`
        ),
        answers.hosting !== 'none' &&
          React.createElement('p', { className: 'text-xs text-neutral-400 mt-2' },
            `+ hosting ${formatPLN(PRICING.hosting[answers.hosting].cost)}/rok`
          )
      ),

      // Summary tags
      React.createElement('div', { className: 'flex flex-wrap gap-2 justify-center mb-6' },
        answers.projectType && React.createElement('span', { className: 'px-2.5 py-1 rounded-full text-xs font-medium bg-brand-500/10 text-brand-600 dark:text-brand-400' },
          PRICING.projectType[answers.projectType]?.label
        ),
        React.createElement('span', { className: 'px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300' },
          `${answers.pages} podstron`
        ),
        answers.integrations.length > 0 &&
          React.createElement('span', { className: 'px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300' },
            `${answers.integrations.length} integracje`
          ),
        answers.deadline && React.createElement('span', { className: 'px-2.5 py-1 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300' },
          PRICING.deadline[answers.deadline]?.label
        )
      ),

      !submitted
        ? React.createElement('div', null,
            React.createElement('p', { className: 'text-sm text-neutral-500 dark:text-neutral-400 mb-4' },
              'Podaj swoje dane, żebyśmy mogli wysłać Ci szczegółową ofertę.'
            ),
            React.createElement('div', { className: 'flex flex-col sm:flex-row gap-3 max-w-md mx-auto mb-4' },
              React.createElement('input', {
                type: 'text', placeholder: 'Twoje imię / firma',
                value: answers.companyName,
                onChange: e => set('companyName', e.target.value),
                className: 'flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500'
              }),
              React.createElement('input', {
                type: 'email', placeholder: 'Twój email',
                value: answers.contactEmail,
                onChange: e => set('contactEmail', e.target.value),
                className: 'flex-1 px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-950 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500'
              })
            ),
            React.createElement('button', {
              type: 'button',
              onClick: () => {
                // TODO: POST to /api/estimate with answers + estimate
                if (answers.contactEmail) setSubmitted(true);
              },
              disabled: !answers.contactEmail,
              className: `inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-sm transition-all ${
                answers.contactEmail
                  ? 'bg-brand-500 text-white hover:bg-brand-600 active:scale-95 shadow-lg shadow-brand-500/20'
                  : 'bg-neutral-200 dark:bg-neutral-800 text-neutral-400 cursor-not-allowed'
              }`
            }, 'Wyślij zapytanie 🚀')
          )
        : React.createElement('div', { className: 'p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800' },
            React.createElement('p', { className: 'text-green-700 dark:text-green-400 font-semibold text-sm' }, '✓ Gotowe! Odezwiemy się w ciągu 24h roboczych.'),
            React.createElement('p', { className: 'text-green-600 dark:text-green-500 text-xs mt-1' }, `Na adres: ${answers.contactEmail}`)
          ),

      React.createElement('button', {
        type: 'button',
        onClick: () => { setStep(1); setAnswers({ projectType:'', pages:5, design:'', cms:'', integrations:[], seoPackage:'', deadline:'', hosting:'', companyName:'', contactEmail:'' }); setSubmitted(false); },
        className: 'mt-4 text-xs text-neutral-400 hover:text-brand-500 transition-colors underline underline-offset-2'
      }, 'Zacznij od nowa')
    );
  }

  // ── Calculator shell ────────────────────────────────────────────────────────
  return React.createElement('div', {
    className: 'bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 shadow-sm',
    role: 'form',
    'aria-label': 'Kalkulator kosztów projektu'
  },
    renderStep()
  );
}

// ── Mount ─────────────────────────────────────────────────────────────────────
const calcRoot = document.getElementById('cost-calculator-root');
if (calcRoot) {
  ReactDOM.createRoot(calcRoot).render(React.createElement(CostCalculator));
}
