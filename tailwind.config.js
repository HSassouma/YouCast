import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * YouCast design tokens: a crisp learning-platform blue with a mauve accent.
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                page: '#F5F6FF',

                brand: {
                    50: '#F1F4FF',
                    100: '#E4E9FF',
                    200: '#CDD6FF',
                    300: '#AAB9FF',
                    400: '#8195FF',
                    500: '#6179F4',
                    600: '#4D63DB',
                    700: '#3E50B8',
                    800: '#303F91',
                    900: '#252F6B',
                    950: '#1A214A',
                },

                slate: {
                    50: '#FAFAFF',
                    100: '#F0F1FA',
                    200: '#DFE1F0',
                    300: '#C8CBE0',
                    400: '#9699B5',
                    500: '#6F728F',
                    600: '#555873',
                    700: '#3E405C',
                    800: '#2E304A',
                    900: '#20213A',
                    950: '#151629',
                },
                gray: {
                    50: '#FAFAFF',
                    100: '#F0F1FA',
                    200: '#DFE1F0',
                    300: '#C8CBE0',
                    400: '#9699B5',
                    500: '#6F728F',
                    600: '#555873',
                    700: '#3E405C',
                    800: '#2E304A',
                    900: '#20213A',
                    950: '#151629',
                },

                success: {
                    50: '#ECFDF3',
                    100: '#D1FADF',
                    200: '#A6F4C5',
                    500: '#12B76A',
                    600: '#039855',
                    700: '#027A48',
                    800: '#05603A',
                },
                warning: {
                    50: '#FFFAEB',
                    100: '#FEF0C7',
                    200: '#FEDF89',
                    500: '#F79009',
                    600: '#DC6803',
                    700: '#B54708',
                    800: '#93370D',
                },
                danger: {
                    50: '#FEF3F2',
                    100: '#FEE4E2',
                    200: '#FECDCA',
                    500: '#F04438',
                    600: '#D92D20',
                    700: '#B42318',
                    800: '#912018',
                },
            },

            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Space Grotesk', 'Figtree', ...defaultTheme.fontFamily.sans],
            },

            fontSize: {
                xs: ['0.75rem', { lineHeight: '1rem' }],
                sm: ['0.875rem', { lineHeight: '1.3rem' }],
                base: ['1rem', { lineHeight: '1.65rem' }],
                lg: ['1.125rem', { lineHeight: '1.7rem' }],
                xl: ['1.25rem', { lineHeight: '1.8rem' }],
                '2xl': ['1.5rem', { lineHeight: '2rem', letterSpacing: '-0.011em' }],
                '3xl': ['1.875rem', { lineHeight: '2.3rem', letterSpacing: '-0.016em' }],
                '4xl': ['2.25rem', { lineHeight: '2.55rem', letterSpacing: '-0.021em' }],
                '5xl': ['3rem', { lineHeight: '3.1rem', letterSpacing: '-0.026em' }],
            },

            borderRadius: {
                xl: '0.5rem',
                '2xl': '0.625rem',
                '3xl': '0.75rem',
            },

            boxShadow: {
                soft: '0 2px 8px 0 rgb(45 55 125 / 0.05)',
                card: '0 4px 14px -10px rgb(45 55 125 / 0.2), 0 1px 2px rgb(45 55 125 / 0.06)',
                raised: '0 14px 34px -20px rgb(45 55 125 / 0.28), 0 4px 10px -7px rgb(45 55 125 / 0.12)',
                modal: '0 24px 60px -24px rgb(45 55 125 / 0.32)',
            },

            maxWidth: {
                content: '76rem',
                prose: '68ch',
            },

            transitionDuration: {
                DEFAULT: '150ms',
            },
        },
    },

    plugins: [forms],
};
