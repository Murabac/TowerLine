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
                sans: [...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    DEFAULT: '#1B4D3E',
                    dark: '#14382C',
                    light: '#A8C5B5',
                    muted: '#2D6A57',
                },
                surface: '#F7F8F6',
                health: {
                    good: '#22C55E',
                    attention: '#F59E0B',
                    critical: '#DC2626',
                    unknown: '#6B7280',
                    construction: '#2563EB',
                },
            },
        },
    },

    plugins: [forms],
};
