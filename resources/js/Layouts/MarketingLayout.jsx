import Navbar from '@/Components/Marketing/Navbar';
import Footer from '@/Components/Marketing/Footer';
import CookieBanner from '@/Components/Marketing/CookieBanner';
import useConsent from '@/Hooks/useConsent';
import useMetaPixel from '@/Hooks/useMetaPixel';
import { ConsentContext } from '@/Contexts/ConsentContext';

function TrackingInit() {
    useMetaPixel();
    return null;
}

// Height of the top banner in pixels — must match the banner's rendered height (py-2 + text-xs ≈ 32px)
const BANNER_HEIGHT = 32;

export default function MarketingLayout({ children, auth, footer, topBanner = null }) {
    const consent = useConsent();

    return (
        <ConsentContext.Provider value={consent}>
            <div className="min-h-screen w-full flex flex-col gap-2 overflow-x-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white">
                {/* Top announcement banner — rendered above the fixed Navbar */}
                {topBanner && (
                    <div
                        className="fixed inset-x-0 top-0 z-[55]"
                        style={{ height: BANNER_HEIGHT }}
                    >
                        {topBanner}
                    </div>
                )}
                <Navbar auth={auth} topOffset={topBanner ? BANNER_HEIGHT : 0} />
                <main className="border-8 border-amber-700">{children}</main>
                {footer && <Footer data={footer} />}
                <CookieBanner />
                <TrackingInit />
            </div>
        </ConsentContext.Provider>
    );
}
