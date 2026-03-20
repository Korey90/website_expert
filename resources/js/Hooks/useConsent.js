import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'cookie_consent';
const CONSENT_VERSION = '1.0';

const defaultConsent = {
    necessary:   true,
    analytics:   false,
    marketing:   false,
    preferences: false,
};

function readConsent() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (parsed.version !== CONSENT_VERSION) return null;
        return parsed;
    } catch {
        return null;
    }
}

function writeConsent(consent) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
        ...consent,
        version:   CONSENT_VERSION,
        timestamp: new Date().toISOString(),
    }));
}

function pushConsentToGTM(consent) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event:                 'consent_update',
        analytics_storage:     consent.analytics   ? 'granted' : 'denied',
        ad_storage:            consent.marketing   ? 'granted' : 'denied',
        functionality_storage: consent.preferences ? 'granted' : 'denied',
        security_storage:      'granted',
    });
    if (typeof window.gtag === 'function') {
        window.gtag('consent', 'update', {
            analytics_storage:     consent.analytics   ? 'granted' : 'denied',
            ad_storage:            consent.marketing   ? 'granted' : 'denied',
            functionality_storage: consent.preferences ? 'granted' : 'denied',
        });
        // Wyślij page_view ręcznie — na wypadek gdyby wait_for_update już minął
        if (consent.analytics) {
            window.dataLayer.push({ event: 'page_view_after_consent' });
        }
    }
    // Meta Pixel — inicjalizuj dopiero przy pierwszej zgodzie marketingowej
    if (window._metaPixelId && consent.marketing && typeof window.fbq === 'undefined') {
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        window.fbq('init', window._metaPixelId);
        window.fbq('track', 'PageView');
    }
}

export default function useConsent() {
    const [consent, setConsent]       = useState(defaultConsent);
    const [resolved, setResolved]     = useState(false);
    const [bannerOpen, setBannerOpen] = useState(false);

    useEffect(() => {
        const saved = readConsent();
        if (saved) {
            setConsent(saved);
            setResolved(true);
            pushConsentToGTM(saved);
        } else {
            setBannerOpen(true);
        }
    }, []);

    const acceptAll = useCallback(() => {
        const full = { necessary: true, analytics: true, marketing: true, preferences: true };
        writeConsent(full);
        setConsent(full);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(full);
    }, []);

    const rejectAll = useCallback(() => {
        const minimal = { ...defaultConsent };
        writeConsent(minimal);
        setConsent(minimal);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(minimal);
    }, []);

    const saveCustom = useCallback((custom) => {
        const merged = { ...defaultConsent, ...custom, necessary: true };
        writeConsent(merged);
        setConsent(merged);
        setResolved(true);
        setBannerOpen(false);
        pushConsentToGTM(merged);
    }, []);

    const reopenBanner = useCallback(() => setBannerOpen(true), []);

    return { consent, resolved, bannerOpen, acceptAll, rejectAll, saveCustom, reopenBanner };
}
