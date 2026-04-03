import CtaSection from '@/Components/LandingPage/PublicSection/CtaSection';
import FaqSection from '@/Components/LandingPage/PublicSection/FaqSection';
import FormSection from '@/Components/LandingPage/PublicSection/FormSection';
import HeroSection from '@/Components/LandingPage/PublicSection/HeroSection';
import SectionsSection from '@/Components/LandingPage/PublicSection/SectionsSection';
import TestimonialsSection from '@/Components/LandingPage/PublicSection/TestimonialsSection';
import TextSection from '@/Components/LandingPage/PublicSection/TextSection';
import VideoSection from '@/Components/LandingPage/PublicSection/VideoSection';

const SECTION_COMPONENTS = {
    hero: HeroSection,
    features: SectionsSection,
    testimonials: TestimonialsSection,
    cta: CtaSection,
    form: FormSection,
    faq: FaqSection,
    text: TextSection,
    video: VideoSection,
};

function normalizeSection(section) {
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
        settings: {
            visible: true,
            ...(section.settings ?? {}),
        },
    };
}

export default function LandingPageSectionRenderer({ sections = [], slug }) {
    return sections
        .map(normalizeSection)
        .filter((section) => section.is_visible !== false && section.settings.visible !== false)
        .map((section, index) => {
            const Component = SECTION_COMPONENTS[section.type];

            if (!Component) {
                return null;
            }

            return (
                <Component
                    key={section.id ?? `${section.type}-${index}`}
                    content={section.content}
                    settings={section.settings}
                    slug={slug}
                />
            );
        });
}