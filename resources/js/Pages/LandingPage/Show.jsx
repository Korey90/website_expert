import LandingPageSectionRenderer from '@/Components/LandingPage/LandingPageSectionRenderer';
import LandingPageSeo from '@/Components/LandingPage/LandingPageSeo';

export default function Show({ landingPage = {}, sections = [], isPreview = false }) {
    return (
        <>
            <LandingPageSeo landingPage={landingPage} />

            {isPreview && (
                <div className="sticky top-0 z-50 flex items-center justify-center gap-2 bg-amber-500 px-4 py-2 text-sm font-semibold text-amber-950">
                    <span>Preview mode — this page is not live and form submissions are disabled.</span>
                </div>
            )}

            <div className="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-white">
                <LandingPageSectionRenderer sections={sections} slug={landingPage.slug} isPreview={isPreview} />

                {sections.length === 0 && (
                    <div className="flex min-h-screen items-center justify-center">
                        <p className="text-gray-400 dark:text-gray-500 text-lg">This page has no content yet.</p>
                    </div>
                )}
            </div>
        </>
    );
}
