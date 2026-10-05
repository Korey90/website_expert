import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { GoogleReCaptchaProvider } from 'react-google-recaptcha-v3';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const recaptchaSiteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || '';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                [`./Pages/${name}.tsx`, `./Pages/${name}.jsx`],
                import.meta.glob('./Pages/**/*.{jsx,tsx}'),
            ),
        setup: ({ App, props }) => (
            <GoogleReCaptchaProvider reCaptchaKey={recaptchaSiteKey}>
                <App {...props} />
            </GoogleReCaptchaProvider>
        ),
    }),
);
