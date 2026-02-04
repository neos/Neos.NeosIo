export default function (ctx) {
    return {
        plugins: {
            "postcss-import": {
                resolve: ctx.resolve,
            },
            "@tailwindcss/postcss": true,
        },
    };
}
