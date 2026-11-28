import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['VT323', ...defaultTheme.fontFamily.mono],
                pixel: ['"Press Start 2P"', ...defaultTheme.fontFamily.mono],
                mono: ['VT323', ...defaultTheme.fontFamily.mono],
            },
            // Theme tokens from resources/css/app.css, they follow the color scheme
            colors: {
                page: token('page'),
                surface: token('surface'),
                ink: token('ink'),
                muted: token('muted'),
                line: token('line'),
                shade: token('shade'),
                primary: token('primary'),
                success: token('success'),
                warning: token('warning'),
                error: token('error'),
            },
        },
    },

    // Inputs are styled with nes.css; the plugin only applies to form-* classes
    plugins: [forms({ strategy: 'class' })],
};
