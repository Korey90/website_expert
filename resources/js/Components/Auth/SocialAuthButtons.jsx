import { usePage } from '@inertiajs/react';

const T = {
    google_login:    { en: 'Continue with Google',   pl: 'Kontynuuj z Google',      pt: 'Continuar com Google' },
    facebook_login:  { en: 'Continue with Facebook', pl: 'Kontynuuj z Facebook',     pt: 'Continuar com Facebook' },
    google_register: { en: 'Sign up with Google',    pl: 'Zarejestruj się przez Google',   pt: 'Cadastrar com Google' },
    facebook_register: { en: 'Sign up with Facebook', pl: 'Zarejestruj się przez Facebook', pt: 'Cadastrar com Facebook' },
};

/**
 * Google and Facebook OAuth buttons.
 * Usage: <SocialAuthButtons mode="login" /> or mode="register"
 */
export default function SocialAuthButtons({ mode = 'login' }) {
    const { locale = 'en' } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const label = mode === 'register'
        ? { google: t('google_register'), facebook: t('facebook_register') }
        : { google: t('google_login'), facebook: t('facebook_login') };

    return (
        <div className="space-y-3">
            {/* Google */}
            <a
                href={route('social.redirect', { provider: 'google' })}
                className="flex w-full items-center justify-center gap-3 rounded-lg border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-3 text-sm font-medium text-neutral-900 dark:text-white hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors duration-200"
            >
                <GoogleIcon />
                {label.google}
            </a>

            {/* Facebook */}
            <a
                href={route('social.redirect', { provider: 'facebook' })}
                className="flex w-full items-center justify-center gap-3 rounded-lg bg-[#1877F2] hover:bg-[#166FE5] px-4 py-3 text-sm font-medium text-white transition-colors duration-200"
            >
                <FacebookIcon />
                {label.facebook}
            </a>
        </div>
    );
}

function GoogleIcon() {
    return (
        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615Z" fill="#4285F4"/>
            <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18Z" fill="#34A853"/>
            <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332Z" fill="#FBBC05"/>
            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58Z" fill="#EA4335"/>
        </svg>
    );
}

function FacebookIcon() {
    return (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="white" aria-hidden="true">
            <path d="M24 12.073C24 5.40365 18.6274 0 12 0S0 5.40365 0 12.073C0 18.1 4.38826 23.0943 10.125 24v-8.437H7.078V12.07H10.125V9.41c0-3.036 1.791-4.716 4.533-4.716 1.313 0 2.686.236 2.686.236V7.91h-1.513c-1.49 0-1.956.935-1.956 1.893v2.27h3.328l-.532 3.493H13.875V24C19.6117 23.0943 24 18.1 24 12.073Z"/>
        </svg>
    );
}
