/**
 * Pass an object with the following properties to the array:
 *
 * packageName   <string>  (optional) If you are not in a Monorepo (and in a package), leave this value blank
 * filename      <string>  (optional) (default: `Main.js`) The name of the entry file
 * entryPath     <string>  (optional) (default: `Resources/Private/Fusion`) The entry path, relative to the package
 * publicPath    <string>  (optional) (default: `Resources/Public`) The public path, relative to the package
 * hasSourceMap  <boolean> (optional) (default: `true`) Flag to toggle source map generation
 */

const packages = [
    {
        packageName: 'Neos.NeosIo.ServiceOfferings',
        filename: 'Main.tsx',
        alias: {
            "react": "preact/compat",
            "react-dom/test-utils": "preact/test-utils",
            "react-dom": "preact/compat",
        }
    },
    { packageName: 'Neos.MarketPlace' },
    { packageName: 'Neos.NeosConIo' },
    { packageName: 'Neos.NeosIo' }
];

export default packages;
