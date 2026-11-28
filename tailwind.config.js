import forms from '@tailwindcss/forms';

/**
 * Values come from CSS variables set by the theme (resources/css/themes/*),
 * so a theme can change colors, fonts and the type scale without touching
 * the markup. See resources/css/app.css for the full contract.
 */
const color = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const size = (name) => [`var(--text-${name})`, `var(--leading-${name})`];

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
                sans: 'var(--font-body)',
                mono: 'var(--font-mono)',
            },
            // Type scale by role, smallest to largest
            fontSize: {
                note: size('note'),
                small: size('small'),
                body: size('body'),
                large: size('large'),
                title: size('title'),
                display: size('display'),
                hero: size('hero'),
            },
            colors: {
                page: color('page'),
                surface: color('surface'),
                ink: color('ink'),
                muted: color('muted'),
                line: color('line'),
                shade: color('shade'),
                primary: color('primary'),
                success: color('success'),
                warning: color('warning'),
                error: color('error'),
            },
        },
    },

    // Inputs are styled by the theme; the plugin only applies to form-* classes
    plugins: [forms({ strategy: 'class' })],
};
