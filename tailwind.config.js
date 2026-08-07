import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Bleu "cloud" sobre — inspiré de la palette d'entreprise Salesforce.
                brand: {
                    50: '#eef7fe',
                    100: '#dbeefd',
                    200: '#b3ddfb',
                    300: '#7cc3f7',
                    400: '#3aa1ef',
                    500: '#0176d3',
                    600: '#0161ab',
                    700: '#014486',
                    800: '#02386b',
                    900: '#032d60',
                },
                ink: {
                    DEFAULT: '#16325c',
                    soft: '#3e4551',
                    muted: '#706e6b',
                },
                surface: {
                    DEFAULT: '#ffffff',
                    muted: '#f3f3f3',
                    sunken: '#f7f9fb',
                },
                line: '#dddbda',
                success: {
                    DEFAULT: '#04844b',
                    tint: '#e4f5eb',
                },
                warning: {
                    DEFAULT: '#a35200',
                    tint: '#fbeddc',
                },
                danger: {
                    DEFAULT: '#ba0517',
                    tint: '#fbe6e7',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgba(22, 50, 92, 0.06)',
                'card-hover': '0 4px 14px 0 rgba(22, 50, 92, 0.08)',
            },
        },
    },

    plugins: [forms],
};
