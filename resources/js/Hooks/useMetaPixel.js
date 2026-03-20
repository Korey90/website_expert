import { useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { useConsentContext } from '@/Contexts/ConsentContext';

export default function useMetaPixel() {
    const { tracking } = usePage().props;
    const { consent } = useConsentContext();

    useEffect(() => {
        if (!tracking?.pixel_enabled || !tracking?.pixel_id) return;
        if (!consent.marketing) return;
        if (window.fbq) return;

        /* eslint-disable */
        !function(f,b,e,v,n,t,s){
            if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)
        }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        /* eslint-enable */

        window.fbq('init', tracking.pixel_id);
        window.fbq('track', 'PageView');
    }, [tracking?.pixel_id, tracking?.pixel_enabled, consent.marketing]);
}
