import { usePage } from '@inertiajs/react';

/**
 * Hook for portal translations.
 * Returns a translation function t(key, replacements?)
 *
 * Usage:
 *   const t = usePortalTrans();
 *   t('welcome_back', { name: 'John' })  // → 'Welcome back, John'
 */
export default function usePortalTrans() {
    const { portal_translations = {} } = usePage().props;

    return function t(key, replacements = {}) {
        let str = portal_translations[key] ?? key;
        Object.entries(replacements).forEach(([k, v]) => {
            str = str.replace(`:${k}`, v);
        });
        return str;
    };
}
