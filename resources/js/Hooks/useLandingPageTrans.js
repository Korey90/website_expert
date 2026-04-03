import { usePage } from '@inertiajs/react';

function getNestedValue(source, path) {
    return path.split('.').reduce((value, key) => {
        if (value && Object.prototype.hasOwnProperty.call(value, key)) {
            return value[key];
        }

        return undefined;
    }, source);
}

export default function useLandingPageTrans() {
    const { landing_page_translations: translations = {} } = usePage().props;

    return function t(key, replacements = {}) {
        const resolved = getNestedValue(translations, key);
        let message = typeof resolved === 'string' ? resolved : key;

        Object.entries(replacements).forEach(([token, value]) => {
            message = message.replace(`:${token}`, String(value));
        });

        return message;
    };
}