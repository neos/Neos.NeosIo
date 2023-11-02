module.exports = (ctx) => ({
    plugins: {
        "postcss-import": {
            resolve: ctx.resolve,
        },
        "tailwindcss/nesting": true,
        tailwindcss: ctx.tailwindcss,
        "postcss-assets": {
            cachebuster: false,
            basePath: `${ctx.basePath}/`,
            baseUrl: "/_Resources/Static/Packages",
            loadPaths: ["**/Resources/Public/**/*"],
        },
        "postcss-clip-path-polyfill": true,
        autoprefixer: true,
        cssnano: ctx.minify
            ? {
                  preset: ["default", { discardComments: { removeAll: true }, svgo: false }],
              }
            : false,
        "postcss-reporter": {
            clearReportedMessages: true,
        },
    },
});
