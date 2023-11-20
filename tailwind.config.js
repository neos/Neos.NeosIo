const content = require('./Build/Carbon.Pipeline/purge');
const plugin = require('tailwindcss/plugin');
const defaultTheme = require('tailwindcss/defaultTheme')

const pxToRem = (px) => `${px / 16}rem`;

/** @type {import('tailwindcss').Config} */
module.exports = {
    content,
    theme: {
        gradients: {
            blue: '#26224C 0%, #0114C2 100%',
            blueDouble: '#26224C 0%, #0114C2 50%, #26224C 100%',
        },
        colors: {
            transparent: 'transparent',
            current: 'currentColor',
            black: '#000',
            white: '#fff',
            darkblue: '#26224C',
            lightblue: '#009FE3',
            gray: {
                DEFAULT: '#989898',
                light: '#F1F1F1',
            },
            green: {
                DEFAULT: '#2AA72A',
                dark: '#1D751D',
            },
            accent: {
                purple: '#8A3FFC',
                blue: '#0114C2',
                lightblue: '#00CFFD',
                green: '#35BA34',
                yellow: '#F1C21B',
                orange: '#FF832B',
                red: '#FA4D56',
            },
        },
        fontSize: {
            '10xl': pxToRem(72),
            '9xl': pxToRem(64),
            '8xl': pxToRem(57),
            '7xl': pxToRem(45),
            '6xl': pxToRem(40),
            '5xl': pxToRem(36),
            '4xl': pxToRem(32),
            '3xl': pxToRem(28),
            '2xl': pxToRem(24),
            xl: pxToRem(22),
            lg: pxToRem(20),
            md: pxToRem(18),
            base: pxToRem(16),
            sm: pxToRem(14),
            xs: pxToRem(12),
            xxs: pxToRem(10),
        },
        screens: {
            // Mobile
            // from 0px to 474px
            xxs: '375px',
            // Tablet
            xs: '475px',
            sm: '640px',
            // Desktop
            md: '768px',
            mdl: '864px',
            lg: '1024px',
            xl: '1280px',
            '2xl': '1440px',
            // Widescreen
            '3xl': '1600px',
            '4xl': '1920px',
            '5xl': '2160px',
        },
        extend: {
            fontFamily: {
                'sans': ['"Bricolage Grotesque"', ...defaultTheme.fontFamily.sans],
            },
            lineHeight: {
                "auto": "normal"
            }
        },
    },
    plugins: [
        plugin(function ({ addUtilities, e, theme }) {
            const gradients = theme('gradients', {});
            const utilities = Object.entries(gradients).map(([name, gradient]) => ({
                [`.${e(`gradient-${name}`)}`]: {
                    '--tw-gradient-stops': gradient,
                },
            }));

            addUtilities(utilities);
        }),
    ],
};
