export default function (ctx) {
    if (ctx.tailwindcss) {
        return {
            plugins: {
                "postcss-import": {
                    resolve: ctx.resolve,
                },
                "@tailwindcss/postcss": true,
            },
        };
    }

    return {
        plugins: {
            'postcss-import': {
                resolve: ctx.resolve,
            },
            'postcss-assets': {
                cachebuster: false,
                basePath: 'DistributionPackages/',
                baseUrl: '/_Resources/Static/Packages',
                loadPaths: ['**/Resources/Public/**/*']
            },
            'postcss-url': {
                filter: /\/_Resources\/Static\/Packages\/[\w]+\.[\w]+\/Resources\/Public\/.*/,
                url: asset => asset.url.replace('/Resources/Public/', '/')
            },
            'postcss-normalize': {
                allowDuplicates: false,
                forceImport: false
            },
            'postcss-preset-env': {
                stage: 1,
                autoprefixer: false
            },
            'postcss-easing-gradients': {
                colorStops: 15,
                alphaDecimals: 5,
                colorMode: 'lrgb'
            },
            'postcss-vmax': true,
            'postcss-clip-path-polyfill': true,
            'postcss-responsive-type': true,
            'postcss-easings': true,
            'postcss-focus': true,
            'pleeease-filters': true,
            'postcss-quantity-queries': true,
            'postcss-momentum-scrolling': ['scroll', 'auto', 'inherit'],
            'postcss-flexbugs-fixes': true,
            'postcss-calc': true,
            'postcss-round-subpixels': true,
            'postcss-pxtorem': {
                rootValue: 16,
                unitPrecision: 5,
                propList: ['font', 'font-size', 'line-height', 'letter-spacing'],
                selectorBlackList: [],
                replace: true,
                mediaQuery: false,
                minPixelValue: 0
            },
            'postcss-sort-media-queries': true,
            autoprefixer: {
                grid: true
            },
            cssnano: {
                preset: ['default', { discardComments: { removeAll: true }, svgo: false }]
            },
            'postcss-reporter': {
                clearReportedMessages: true
            }
        }
    }

}
