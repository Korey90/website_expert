import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{jsx,tsx}',
    ],

    safelist: [
        // Background (settings.bg)
        'bg-white',
        'bg-neutral-50',
        'bg-neutral-900',
        'bg-neutral-950',
        'bg-brand-500/5',
        'bg-brand-500/10',
        'dark:bg-neutral-950',
        'dark:bg-neutral-900',
        'dark:bg-brand-500/10',
        // Text alignment (per-field _align)
        'text-left',
        'text-center',
        'text-right',
        // Per-field size (_size)
        'text-xs',
        'text-sm',
        'text-base',
        'text-lg',
        'text-xl',
        'text-2xl',
        'text-3xl',
        'text-4xl',
        'text-5xl',
        'text-6xl',
        'sm:text-2xl',
        'sm:text-3xl',
        'sm:text-4xl',
        'sm:text-5xl',
        'sm:text-6xl',
        // Per-field color (_color)
        'text-brand-500',
        'text-white',
        'text-neutral-500',
        'text-neutral-900',
        'dark:text-white',
        'dark:text-neutral-400',
        // Font families
        'font-display',
        'font-sans',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#fff2f0',
                    100: '#ffe0dc',
                    200: '#ffc5be',
                    300: '#ff9d92',
                    400: '#ff6657',
                    500: '#ff2b17',
                    600: '#ed1a07',
                    700: '#c81205',
                    800: '#a51208',
                    900: '#88160c',
                    950: '#4b0702',
                },
            },
            fontFamily: {
                sans:    ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Syne', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
