import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

export default function CtaSection({ content = {}, settings = {} }) {
    const handleClick = (e) => {
        const href = content.cta_url ?? '';
        if (href.startsWith('#')) {
            e.preventDefault();
            const el = document.getElementById(href.slice(1));
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        }
    };

    return (
        <SectionShell settings={settings} backgroundFallback="primary" width="narrow">
            <div className="rounded-[2rem] border border-white/10 bg-white/10 px-6 py-8 text-center shadow-2xl backdrop-blur dark:bg-white/5 sm:px-10 sm:py-12">
                <SectionHeading
                    title={content.headline}
                    subtitle={content.subheadline}
                    align="center"
                    invert
                    className="mb-8"
                />
                {content.cta_text && content.cta_url && (
                    <a
                        href={content.cta_url}
                        onClick={handleClick}
                        className="inline-flex items-center rounded-2xl bg-white px-8 py-4 text-base font-bold text-brand-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-brand-700"
                    >
                        {content.cta_text}
                    </a>
                )}
            </div>
        </SectionShell>
    );
}
