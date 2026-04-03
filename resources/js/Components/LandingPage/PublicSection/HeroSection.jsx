import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

export default function HeroSection({ content = {}, settings = {} }) {
    const ctaUrl = content.cta_url || '#form';
    const isAnchor = ctaUrl.startsWith('#');

    const handleCtaClick = (e) => {
        if (isAnchor) {
            e.preventDefault();
            const target = document.querySelector(ctaUrl);
            target?.scrollIntoView({ behavior: 'smooth' });
        }
    };

    return (
        <SectionShell settings={settings} backgroundFallback="gradient" width="wide" className="overflow-hidden">
            <div className="grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div className="text-center lg:text-left">
                    {content.headline && (
                        <h1 className="font-display text-4xl font-bold leading-[0.95] tracking-tight sm:text-5xl lg:text-7xl">
                            {content.headline}
                        </h1>
                    )}
                    {content.subheadline && (
                        <p className="mx-auto mt-6 max-w-2xl text-base leading-7 text-white/82 sm:text-xl lg:mx-0">
                            {content.subheadline}
                        </p>
                    )}
                    {content.cta_text && (
                        <div className="mt-8 flex justify-center lg:justify-start">
                            <a
                                href={ctaUrl}
                                onClick={handleCtaClick}
                                className="inline-flex items-center justify-center rounded-2xl bg-white px-7 py-4 text-base font-semibold text-brand-700 shadow-2xl transition hover:-translate-y-0.5 hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-white/25"
                            >
                                {content.cta_text}
                            </a>
                        </div>
                    )}
                </div>

                <div className="hidden lg:block">
                    <div className="rounded-[2rem] border border-white/15 bg-white/10 p-6 backdrop-blur">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div className="rounded-2xl bg-white/10 p-4">
                                <p className="text-xs uppercase tracking-[0.25em] text-white/55">Conversion focus</p>
                                <p className="mt-3 text-xl font-semibold text-white">{content.cta_text || 'Get started'}</p>
                            </div>
                            <div className="rounded-2xl bg-black/15 p-4">
                                <p className="text-xs uppercase tracking-[0.25em] text-white/55">Ready for launch</p>
                                <p className="mt-3 text-sm leading-6 text-white/80">Responsive sections, lead capture and SEO metadata are rendered from JSON.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SectionShell>
    );
}
