import packages from './webpack.packages';
import path from 'path';
import MiniCssExtractPlugin from 'mini-css-extract-plugin';
import GlobImporter from 'node-sass-glob-importer';
import TerserPlugin from 'terser-webpack-plugin';

const distFolder = 'DistributionPackages';
const packagesFolder = 'Packages';

function config(
    {
        packageName,
        filename = 'Main.js',
        entryPath = 'Resources/Private/Fusion',
        publicPath = 'Resources/Public',
        hasSourceMap = true
    }: {
        packageName: string;
        filename?: string;
        entryPath?: string;
        publicPath?: string;
        hasSourceMap?: boolean;
    },
    argv: any
): object {
    const isInlineAsset = publicPath == 'Resources/Private/Templates/InlineAssets';
    const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
    const isProduction = argv.mode == 'production';
    hasSourceMap = isInlineAsset ? false : hasSourceMap;

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
            filename: path.join((publicPath = isInlineAsset ? '' : 'Scripts'), `${baseFilename}.js`)
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
                                    includePaths: [
                                        path.resolve(__dirname, distFolder),
                                        path.resolve(__dirname, packagesFolder),
                                        'node_modules'
                                    ]
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
            alias: {
                DistributionPackages: path.resolve(__dirname, distFolder),
                Packages: path.resolve(__dirname, packagesFolder)
            }
        }
    };
}

export default (env, argv) => packages.map(setting => config(setting, argv));
