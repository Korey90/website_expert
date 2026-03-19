import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
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
