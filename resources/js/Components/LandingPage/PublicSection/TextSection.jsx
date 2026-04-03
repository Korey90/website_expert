import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

/**
 * Text / Rich text section — renders server-sanitized HTML body.
 */
export default function TextSection({ content = {}, settings = {} }) {
    const html = content.body ?? content.html ?? '';

    return (
        <SectionShell settings={settings} backgroundFallback="white" width="narrow">
            <SectionHeading title={content.headline} align="left" />
                {html && (
                    <div
                        className="prose prose-gray dark:prose-invert max-w-none prose-headings:font-display prose-a:text-brand-600 dark:prose-a:text-brand-400 prose-a:no-underline hover:prose-a:underline"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                )}
        </SectionShell>
    );
}
