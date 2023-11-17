const content = require("./Build/Carbon.Pipeline/purge");
const plugin = require("tailwindcss/plugin");

/** @type {import('tailwindcss').Config} */
module.exports = {
    content,
    theme: {
        gradient: {
            blue: "#26224C 0%, #0114C2 100%"
        },
        colors: {
            transparent: "transparent",
            current: "currentColor",
            black: "#000",
            white: "#fff",
            darkblue: "#26224C",
            lightblue: "#009FE3",
            gray: {
                DEFAULT: "#989898",
                light: "#F1F1F1",
            },
            green: {
                DEFAULT: "#2AA72A",
                dark: "#1D751D",
            },
            accent: {
                purple: "#8A3FFC",
                blue: "#0114C2",
                lightblue: "#00CFFD",
                green: "#35BA34",
                yellow: "#F1C21B",
                orange: "#FF832B",
                red: "#FA4D56",
            }
        },
        extend: {},
    },
    plugins: [
        plugin(function ({addUtilities, e, theme}) {
            const gradients = theme('gradients', {})
            const utilities = Object.entries(gradients).map(([name, gradient]) => ({
                [`.${e(`gradient-${name}`)}`]: {
                    '--gradient-color-stops': gradient
                }
            }));

            addUtilities(utilities);
        }),
    ],
};
