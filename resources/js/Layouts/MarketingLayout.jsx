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

export default function MarketingLayout({ children, auth, navbar, footer }) {
    const consent = useConsent();

    return (
        <ConsentContext.Provider value={consent}>
            <div className="min-h-screen w-full overflow-x-hidden bg-white dark:bg-neutral-950 text-neutral-900 dark:text-white">
                <Navbar auth={auth} data={navbar} />
                <main className="overflow-x-hidden">{children}</main>
                <Footer data={footer} />
                <CookieBanner />
                <TrackingInit />
            </div>
        </ConsentContext.Provider>
    );
}
