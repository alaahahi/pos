import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'Cairo', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                shop: {
                    50: '#f5f7ff',
                    100: '#ebeeff',
                    200: '#d4daff',
                    300: '#b4bfff',
                    400: '#8a94ff',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                },
            },
            boxShadow: {
                shop: '0 1px 3px 0 rgb(15 23 42 / 0.06), 0 1px 2px -1px rgb(15 23 42 / 0.06)',
                'shop-md': '0 4px 6px -1px rgb(15 23 42 / 0.08), 0 2px 4px -2px rgb(15 23 42 / 0.06)',
                'shop-lg': '0 10px 15px -3px rgb(15 23 42 / 0.08), 0 4px 6px -4px rgb(15 23 42 / 0.06)',
            },
            animation: {
                'shop-fade-in': 'shopFadeIn 0.2s ease-out',
                'shop-slide-up': 'shopSlideUp 0.25s ease-out',
            },
            keyframes: {
                shopFadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                shopSlideUp: {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },

    plugins: [forms],
};
