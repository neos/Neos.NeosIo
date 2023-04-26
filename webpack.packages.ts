/**
 * Pass an object with the following properties to the array:
 *
 * packageName   <string>  (optional) If you are not in a Monorepo (and in a package), leave this value blank
 * filename      <string>  (optional) (default: `Main.js`) The name of the entry file
 * inline        <boolean> (optional) (default: `false`) Flag to toggle if the file should be written to `Resources/Private/Templates/InlineAssets`, sourceMaps get turned off
 * entryPath     <string>  (optional) (default: `Resources/Private/Fusion`) The entry path, relative to the package
 * publicPath    <string>  (optional) (default: `Resources/Public`) The public path, relative to the package
 * hasSourceMap  <boolean> (optional) (default: `true`) Flag to toggle source map generation
 * alias         <object>  (optional) (default: `{}`) Add your own, package-specific alias
 */

const packages = [
    {
        packageName: 'Neos.NeosIo.ServiceOfferings',
        filename: 'Main.tsx',
        alias: {
            react: 'preact/compat',
            'react-dom/test-utils': 'preact/test-utils',
            'react-dom': 'preact/compat'
        }
    },
    {
        packageName: 'Neos.NeosIo.ReleasePlan',
        filename: 'Main.tsx',
        alias: {
            react: 'preact/compat',
            'react-dom/test-utils': 'preact/test-utils',
            'react-dom': 'preact/compat'
        }
    },
    {
        packageName: 'Neos.NeosIo.CaseStudies',
        filename: 'Main.tsx',
        alias: {
            react: 'preact/compat',
            'react-dom/test-utils': 'preact/test-utils',
            'react-dom': 'preact/compat'
        }
    },
    { packageName: 'Neos.MarketPlace' },
    { packageName: 'Neos.NeosConIo' },
    { packageName: 'Neos.NeosIo' }
];

export default packages;
