import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    badge: {
        en: 'SaaS Landing Page',
        pl: 'SaaS Landing Page',
        pt: 'Landing Page SaaS',
    },
    title: {
        en: 'Launch a lead-generating SaaS landing page without rebuilding your whole stack.',
        pl: 'Uruchom landing page pod SaaS, ktora generuje leady bez przebudowy calego stacku.',
        pt: 'Lance uma landing page SaaS que gere leads sem reconstruir toda a stack.',
    },
    subtitle: {
        en: 'AI-assisted page generation, structured sections, built-in lead capture and CRM handoff in one flow designed for agencies and productised services.',
        pl: 'Generowanie strony wspierane przez AI, strukturalne sekcje, wbudowany lead capture i przekazanie do CRM w jednym flow zaprojektowanym dla agencji oraz produktowych uslug.',
        pt: 'Geracao de pagina assistida por IA, secoes estruturadas, captacao de leads e envio para CRM num unico fluxo pensado para agencias e servicos productizados.',
    },
    primaryCta: {
        guest: {
            en: 'Start with SaaS flow',
            pl: 'Zacznij z flow SaaS',
            pt: 'Comecar fluxo SaaS',
        },
        auth: {
            en: 'Open AI landing builder',
            pl: 'Otworz AI landing builder',
            pt: 'Abrir builder AI',
        },
    },
    secondaryCta: {
        en: 'Talk to us about implementation',
        pl: 'Porozmawiajmy o wdrozeniu',
        pt: 'Fale connosco sobre a implementacao',
    },
    points: [
        {
            en: 'Reusable landing structure built for rapid offer testing',
            pl: 'Reuzywalna struktura landing page pod szybkie testowanie ofert',
            pt: 'Estrutura reutilizavel para testar ofertas rapidamente',
        },
        {
            en: 'Public form already connected to lead pipeline and CRM automation',
            pl: 'Publiczny formularz juz podlaczony do pipeline leadow i automatyzacji CRM',
            pt: 'Formulario publico ja ligado ao pipeline de leads e automacao CRM',
        },
        {
            en: 'Prepared for multi-business SaaS rollout instead of one-off brochure pages',
            pl: 'Przygotowane pod rollout multi-business SaaS zamiast jednorazowych stron firmowych',
            pt: 'Preparado para rollout SaaS multi-business em vez de paginas isoladas',
        },
    ],
    pillars: [
        {
            eyebrow: '01',
            title: {
                en: 'AI draft in minutes',
                pl: 'AI draft w kilka minut',
                pt: 'Rascunho AI em minutos',
            },
            body: {
                en: 'Generate a conversion-ready landing structure from business profile, campaign brief and offer context.',
                pl: 'Wygeneruj strukture landing page gotowa do konwersji na podstawie Business Profile, briefu i kontekstu oferty.',
                pt: 'Gere uma estrutura pronta para conversao com base no perfil, briefing e oferta.',
            },
        },
        {
            eyebrow: '02',
            title: {
                en: 'Publish fast, iterate safely',
                pl: 'Publikuj szybko, iteruj bezpiecznie',
                pt: 'Publique rapido e itere com seguranca',
            },
            body: {
                en: 'Draft, save, publish and manage sections without losing control over SEO, slug policy or public runtime.',
                pl: 'Tworz draft, zapisuj, publikuj i zarzadzaj sekcjami bez utraty kontroli nad SEO, slugami i publicznym runtime.',
                pt: 'Crie draft, guarde, publique e ajuste secoes sem perder controlo sobre SEO, slug e runtime publico.',
            },
        },
        {
            eyebrow: '03',
            title: {
                en: 'Leads go straight into CRM',
                pl: 'Leady trafiaja od razu do CRM',
                pt: 'Os leads entram diretamente no CRM',
            },
            body: {
                en: 'Capture enquiries, assign ownership, trigger automations and keep attribution attached to the sales pipeline.',
                pl: 'Zbieraj zapytania, przypisuj odpowiedzialnosc, odpalaj automatyzacje i zachowuj atrybucje w pipeline sprzedazowym.',
                pt: 'Capte pedidos, atribua responsavel, dispare automacoes e mantenha a atribuicao no pipeline comercial.',
            },
        },
    ],
    flowLabel: {
        en: 'Runtime flow',
        pl: 'Runtime flow',
        pt: 'Fluxo runtime',
    },
    flowSteps: [
        { en: 'AI Generator', pl: 'AI Generator', pt: 'AI Generator' },
        { en: 'Draft & Publish', pl: 'Draft i Publish', pt: 'Draft e Publish' },
        { en: 'Lead Capture', pl: 'Lead Capture', pt: 'Lead Capture' },
        { en: 'CRM & Automations', pl: 'CRM i Automatyzacje', pt: 'CRM e Automacoes' },
    ],
};

export default function SaasLandingSection({ data = null }) {
    const { locale = 'en', auth } = usePage().props;

    // Resolve translatable key: t(obj, 'key') → obj.key_pl → obj.key_en → obj.key → ''
    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    // Simple object resolver: tObj({en,pl,pt}) → value for current locale
    const tObj = (obj) => obj?.[locale] ?? obj?.en ?? '';

    const isAuthenticated = Boolean(auth?.user);
    const extra = data?.extra ?? {};

    // --- Badge ---
    const badge = t(extra, 'badge') || tObj(DEFAULTS.badge);

    // --- Title / Subtitle ---
    const title    = data?.title?.[locale]    ?? data?.title?.en    ?? tObj(DEFAULTS.title);
    const subtitle = data?.subtitle?.[locale] ?? data?.subtitle?.en ?? tObj(DEFAULTS.subtitle);

    // --- Primary CTA ---
    const primaryCtaText = isAuthenticated
        ? (t(extra, 'primary_cta_auth') || tObj(DEFAULTS.primaryCta.auth))
        : (data?.button_text?.[locale] ?? data?.button_text?.en ?? tObj(DEFAULTS.primaryCta.guest));
    const primaryHref = isAuthenticated
        ? route('landing-pages.ai.create')
        : (data?.button_url ?? route('register'));

    // --- Secondary CTA ---
    const secondaryCtaText = t(extra, 'secondary_cta') || tObj(DEFAULTS.secondaryCta);
    const secondaryCtaUrl  = extra.secondary_cta_url ?? '#contact';

    // --- Points ---
    const points = extra.points?.length ? extra.points : DEFAULTS.points;

    // --- Pillars ---
    const pillars = extra.pillars?.length ? extra.pillars : DEFAULTS.pillars;
    // Pillar fields come in two shapes:
    //   DB format:       { title_en, title_pl, body_en, body_pl, ... }
    //   DEFAULTS format: { title: {en, pl, pt}, body: {en, pl, pt} }
    const tPillarTitle = (p) => p?.[`title_${locale}`] ?? p?.title_en ?? tObj(p.title) ?? '';
    const tPillarBody  = (p) => p?.[`body_${locale}`]  ?? p?.body_en  ?? tObj(p.body)  ?? '';

    // --- Flow ---
    const flowLabel = t(extra, 'flow_label') || tObj(DEFAULTS.flowLabel);
    const flowSteps = extra.flow_steps?.length ? extra.flow_steps : DEFAULTS.flowSteps;

    return (
        <section id="saas-landing" className="relative overflow-hidden bg-white py-20 text-neutral-900 dark:bg-neutral-950 dark:text-white md:py-28">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,43,23,0.12),transparent_34%)]" aria-hidden="true" />
            <div className="absolute inset-0 hidden dark:block bg-[radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.08),transparent_28%)]" aria-hidden="true" />
            <div className="absolute left-1/2 top-8 h-72 w-72 -translate-x-1/2 rounded-full bg-brand-500/10 blur-3xl dark:bg-brand-500/15" aria-hidden="true" />

            <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid items-start gap-10 lg:grid-cols-[minmax(0,1.15fr)_420px]">
                    <div className="reveal">
                        <span className="inline-flex items-center gap-2 rounded-full border border-neutral-300 bg-neutral-100 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.25em] text-neutral-600 dark:border-white/15 dark:bg-white/5 dark:text-white/72">
                            <span className="h-2 w-2 rounded-full bg-brand-500" />
                            {badge}
                        </span>

                        <h2 className="mt-6 max-w-4xl font-display text-4xl font-black leading-[1.02] tracking-tight text-neutral-900 dark:text-white sm:text-5xl lg:text-6xl">
                            {title}
                        </h2>

                        <p className="mt-6 max-w-3xl text-lg leading-8 text-neutral-600 dark:text-white/72">
                            {subtitle}
                        </p>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                href={primaryHref}
                                className="inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-500 px-7 py-4 text-sm font-bold text-white shadow-xl shadow-brand-500/25 transition-all hover:-translate-y-0.5 hover:bg-brand-600"
                            >
                                {primaryCtaText}
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </a>

                            <a
                                href={secondaryCtaUrl}
                                className="inline-flex items-center justify-center gap-2 rounded-2xl border border-neutral-300 bg-neutral-100 px-7 py-4 text-sm font-bold text-neutral-700 transition-all hover:border-brand-500/60 hover:bg-neutral-200 dark:border-white/15 dark:bg-white/5 dark:text-white/86 dark:hover:bg-white/8"
                            >
                                {secondaryCtaText}
                            </a>
                        </div>

                        <div className="mt-10 grid gap-3 sm:grid-cols-3">
                            {points.map((point) => (
                                <div key={point.en} className="flex items-start gap-3 rounded-3xl border border-neutral-200 bg-neutral-50 p-4 dark:border-white/10 dark:bg-white/5 dark:backdrop-blur-sm">
                                    <div className="shrink-0 h-9 w-9 rounded-2xl bg-brand-500/10 p-2 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                                        <svg className="h-full w-full" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </div>
                                    <p className="text-sm leading-6 text-neutral-600 dark:text-white/72">{tObj(point)}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="reveal lg:sticky lg:top-24">
                        <div className="overflow-hidden rounded-4xl border border-neutral-200 bg-white shadow-2xl shadow-neutral-300/40 dark:border-white/10 dark:bg-white/6 dark:shadow-black/20 dark:backdrop-blur">
                            <div className="border-b border-neutral-200 px-5 py-4 dark:border-white/10">
                                <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-500 dark:text-white/45">{flowLabel}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {flowSteps.map((step) => (
                                        <span key={step.en} className="rounded-full border border-neutral-200 bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-600 dark:border-white/10 dark:bg-black/20 dark:text-white/72">
                                            {tObj(step)}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-4 p-5">
                                {pillars.map((pillar, index) => (
                                    <article
                                        key={pillar.eyebrow}
                                        className={[
                                            'rounded-3xl border p-5 transition-colors',
                                            index === 1
                                                ? 'border-brand-500/50 bg-brand-50 dark:border-brand-500/35 dark:bg-brand-500/12'
                                                : 'border-neutral-200 bg-neutral-50 dark:border-white/10 dark:bg-black/20',
                                        ].join(' ')}
                                    >
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-brand-600 dark:text-brand-300/90">{pillar.eyebrow}</p>
                                                <h3 className="mt-2 text-xl font-semibold text-neutral-900 dark:text-white">{tPillarTitle(pillar)}</h3>
                                            </div>
                                            <span className="rounded-full border border-neutral-200 bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-500 dark:border-white/10 dark:bg-white/5 dark:text-white/55">
                                                SaaS
                                            </span>
                                        </div>
                                        <p className="mt-4 text-sm leading-6 text-neutral-600 dark:text-white/68">{tPillarBody(pillar)}</p>
                                    </article>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}