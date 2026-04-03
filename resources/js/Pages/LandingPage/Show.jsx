import LandingPageSectionRenderer from '@/Components/LandingPage/LandingPageSectionRenderer';
import LandingPageSeo from '@/Components/LandingPage/LandingPageSeo';

export default function Show({ landingPage = {}, sections = [] }) {
    return (
        <>
            <LandingPageSeo landingPage={landingPage} />

            <div className="min-h-screen bg-white text-gray-900 dark:bg-gray-950 dark:text-white">
                <LandingPageSectionRenderer sections={sections} slug={landingPage.slug} />

                {sections.length === 0 && (
                    <div className="flex min-h-screen items-center justify-center">
                        <p className="text-gray-400 dark:text-gray-500 text-lg">This page has no content yet.</p>
                    </div>
                )}
            </div>
        </>
    );
}
