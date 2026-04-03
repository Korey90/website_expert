import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';

/**
 * Video embed section — YouTube and Vimeo.
 * Automatically converts watch URLs to embed URLs.
 */
function toEmbedUrl(url) {
    if (!url) return null;

    // youtube.com/watch?v=ID
    const ytWatch = url.match(/youtube\.com\/watch\?.*v=([^&]+)/);
    if (ytWatch) return `https://www.youtube-nocookie.com/embed/${ytWatch[1]}`;

    // youtu.be/ID
    const ytShort = url.match(/youtu\.be\/([^?&]+)/);
    if (ytShort) return `https://www.youtube-nocookie.com/embed/${ytShort[1]}`;

    // vimeo.com/ID
    const vimeo = url.match(/vimeo\.com\/(\d+)/);
    if (vimeo) return `https://player.vimeo.com/video/${vimeo[1]}`;

    // Already an embed URL — return as-is
    return url;
}

export default function VideoSection({ content = {}, settings = {} }) {
    const embedUrl = toEmbedUrl(content.url ?? content.video_url);

    return (
        <SectionShell settings={settings} backgroundFallback="muted" width="wide">
            <SectionHeading title={content.headline} align="center" />

                {embedUrl ? (
                    <div className="aspect-video w-full overflow-hidden rounded-[1.75rem] shadow-2xl">
                        <iframe
                            src={embedUrl}
                            title={content.headline ?? 'Video'}
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                            loading="lazy"
                            className="h-full w-full border-0"
                        />
                    </div>
                ) : (
                    <div className="flex items-center justify-center h-48 rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-700 text-gray-400 dark:text-gray-500 text-sm">
                        No video URL configured
                    </div>
                )}
        </SectionShell>
    );
}
