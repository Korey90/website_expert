import { useForm } from '@inertiajs/react';
import { useState } from 'react';

/**
 * useApiTokens — hook do zarządzania tokenami API firmy.
 */
export function useApiTokens() {
    const [copied, setCopied] = useState(false);

    const form = useForm({ name: '' });

    const create = (e) => {
        e.preventDefault();
        form.post(route('business.api-tokens.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const copyToClipboard = async (text) => {
        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 3000);
        } catch {
            // fallback dla starszych przeglądarek
            const el = document.createElement('textarea');
            el.value = text;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            setCopied(true);
            setTimeout(() => setCopied(false), 3000);
        }
    };

    return { form, create, copied, copyToClipboard };
}
