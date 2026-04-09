import CtaSection from '@/Components/LandingPage/PublicSection/CtaSection';
import FaqSection from '@/Components/LandingPage/PublicSection/FaqSection';
import FeaturesSection from '@/Components/LandingPage/PublicSection/FeaturesSection';
import FormSection from '@/Components/LandingPage/PublicSection/FormSection';
import HeroSection from '@/Components/LandingPage/PublicSection/HeroSection';
import TestimonialsSection from '@/Components/LandingPage/PublicSection/TestimonialsSection';
import TextSection from '@/Components/LandingPage/PublicSection/TextSection';
import VideoSection from '@/Components/LandingPage/PublicSection/VideoSection';

const SECTION_MAP = {
    hero: HeroSection,
    features: FeaturesSection,
    testimonials: TestimonialsSection,
    cta: CtaSection,
    form: FormSection,
    faq: FaqSection,
    text: TextSection,
    video: VideoSection,
};

function normalizePreviewSection(section) {
    const content = section.content ?? {};

    return {
        ...section,
        content: {
            ...content,
            body: content.body ?? content.html ?? '',
            html: content.html ?? content.body ?? '',
            url: content.url ?? content.video_url ?? '',
            video_url: content.video_url ?? content.url ?? '',
        },
    };
}

export default function AiLandingPreview({ variant, t }) {
    if (!variant) {
        return (
            <section className="rounded-[2rem] border border-dashed border-neutral-300 bg-white/70 p-8 text-center shadow-sm dark:border-neutral-700 dark:bg-neutral-900/70">
                <p className="text-xs font-semibold uppercase tracking-[0.25em] text-neutral-400">
                    {t('ai.ui.preview_title')}
                </p>
                <h3 className="mt-3 font-display text-2xl font-semibold text-neutral-900 dark:text-white">
                    {t('ai.ui.preview_empty')}
                </h3>
                <p className="mx-auto mt-3 max-w-lg text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                    {t('ai.ui.preview_note')}
                </p>
            </section>
        );
    }

    const previewSections = (variant.sections ?? []).map(normalizePreviewSection);

    return (
        <section className="overflow-hidden rounded-[2rem] border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex items-center gap-3 border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
                <div className="flex gap-2">
                    <span className="h-3 w-3 rounded-full bg-red-400" />
                    <span className="h-3 w-3 rounded-full bg-amber-400" />
                    <span className="h-3 w-3 rounded-full bg-emerald-400" />
                </div>
                <div className="min-w-0 flex-1 rounded-full border border-neutral-200 bg-neutral-50 px-4 py-2 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                    {t('ai.ui.browser_label')}: /lp/{variant.slug_suggestion ?? 'ai-draft'}
                </div>
            </div>

            <div className="max-h-[980px] overflow-auto bg-white dark:bg-neutral-950">
                {previewSections.map((section, index) => {
                    const Component = SECTION_MAP[section.type];

                    if (!Component || section.settings?.visible === false) {
                        return null;
                    }

                    return (
                        <Component
                            key={`${section.type}-${index}`}
                            content={section.content}
                            settings={section.settings}
                            slug={variant.slug_suggestion ?? 'ai-draft'}
                            isPreview={true}
                        />
                    );
                })}
            </div>
        </section>
    );
}