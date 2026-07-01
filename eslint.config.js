import js from '@eslint/js';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';

const browserGlobals = {
    AbortController: 'readonly',
    Blob: 'readonly',
    CustomEvent: 'readonly',
    DOMParser: 'readonly',
    File: 'readonly',
    FormData: 'readonly',
    Headers: 'readonly',
    IntersectionObserver: 'readonly',
    MutationObserver: 'readonly',
    URL: 'readonly',
    URLSearchParams: 'readonly',
    WebSocket: 'readonly',
    clearInterval: 'readonly',
    clearTimeout: 'readonly',
    confirm: 'readonly',
    console: 'readonly',
    document: 'readonly',
    fetch: 'readonly',
    history: 'readonly',
    localStorage: 'readonly',
    location: 'readonly',
    navigator: 'readonly',
    requestAnimationFrame: 'readonly',
    route: 'readonly',
    setInterval: 'readonly',
    setTimeout: 'readonly',
    sessionStorage: 'readonly',
    window: 'readonly',
};

const frontendFiles = ['resources/js/**/*.{js,jsx}'];

export default [
    {
        ignores: [
            'public/build/**',
            'public/js/filament/**',
            'resources/js/ziggy.js',
            'vendor/**',
        ],
    },
    js.configs.recommended,
    {
        ...react.configs.flat.recommended,
        files: frontendFiles,
    },
    {
        ...react.configs.flat['jsx-runtime'],
        files: frontendFiles,
    },
    {
        files: frontendFiles,
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: browserGlobals,
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
        },
        plugins: {
            'react-hooks': reactHooks,
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
        rules: {
            'no-unused-vars': 'off',
            'react-hooks/rules-of-hooks': 'error',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
            'react/react-in-jsx-scope': 'off',
        },
    },
];
