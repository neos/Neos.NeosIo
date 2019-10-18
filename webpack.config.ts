import packages from './webpack.packages';
import path from 'path';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import GlobImporter from 'node-sass-glob-importer';
import TerserPlugin from 'terser-webpack-plugin';

function config(
    {
        packageName = null,
        filename = 'Main.js',
        entryPath = 'Resources/Private/Fusion',
        publicPath = 'Resources/Public',
        hasSourceMap = true
    }: {
        packageName?: string;
        filename?: string;
        entryPath?: string;
        publicPath?: string;
        hasSourceMap?: boolean;
    },
    argv: any
): object {
    const alias = {};
    const includePaths = [];
    const isInlineAsset = publicPath == 'Resources/Private/Templates/InlineAssets';
    const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
    const isProduction = argv.mode == 'production';
    const distFolder = packageName ? 'DistributionPackages' : '';
    hasSourceMap = isInlineAsset ? false : hasSourceMap;
    packageName = packageName || '';

    if (packageName) {
        // We are in a monorepo
        const distributionPath = path.resolve(__dirname, distFolder);
        const packagesPath = path.resolve(__dirname, 'Packages');
        alias[distFolder] = distributionPath;
        alias['Packages'] = packagesPath;
        includePaths.push(distributionPath);
        includePaths.push(packagesPath);
    }
    includePaths.push('node_modules');

    return {
        mode: isProduction ? 'production' : 'development',
        devtool: isProduction ? 'source-map' : 'nosources-source-map',
        stats: {
            modules: false,
            hash: false,
            version: false,
            timings: true,
            chunks: false,
            children: false,
            source: false,
            publicPath: false
        },
        performance: { hints: false },
        entry: {
            [path.join(packageName, entryPath, filename)]: './' + path.join(packageName, entryPath, filename)
        },
        output: {
            devtoolModuleFilenameTemplate: isProduction
                ? 'webpack://[namespace]/[resource-path]?[loaders]'
                : 'file://[absolute-resource-path]?[loaders]',
            path: path.resolve(__dirname, distFolder, packageName, publicPath),
            filename: path.join(isInlineAsset ? '' : 'Scripts', `${baseFilename}.js`)
        },
        optimization: isProduction
            ? {
                  minimizer: [
                      new TerserPlugin({
                          cache: true,
                          parallel: true,
                          sourceMap: hasSourceMap
                      })
                  ]
              }
            : {},
        plugins: [
            new MiniCssExtractPlugin({
                filename: path.join(isInlineAsset ? '' : 'Styles', `${baseFilename}.css`)
            })
        ],
        context: path.resolve(__dirname, distFolder),
        module: {
            rules: [
                {
                    test: /\.(ts|js)x?$/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            cacheDirectory: true
                        }
                    }
                },
                {
                    // do not test for css to prevent double builds
                    // when two packages depend on another
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: isInlineAsset ? false : hasSourceMap,
                                importLoaders: 2
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                sourceMap: isInlineAsset ? false : hasSourceMap
                            }
                        },
                        {
                            loader: 'cache-loader'
                        },
                        {
                            loader: 'sass-loader',

                            options: {
                                sourceMap: isInlineAsset ? false : hasSourceMap,
                                // absolute paths for SCSS
                                sassOptions: {
                                    importer: GlobImporter(),
                                    includePaths: includePaths
                                }
                            }
                        }
                    ]
                }
            ]
        },
        resolve: {
            extensions: ['*', '.js', '.jsx', '.ts', '.tsx', '.scss'],
            // absolute paths for JS and SCSS related files
            alias: alias
        }
    };
}

export default (env, argv) => packages.map(setting => config(setting, argv));
