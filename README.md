# Neos.NeosIo

> The official neos.io website package.

## Setup & Installation

Clone the repository, and setup Neos as always.

__Note: We require [nvm](https://github.com/creationix/nvm#install-script) as well as the `npm` and `node` binaries to be installed on your system.
If you've installed `nvm` make sure that the node LTS version `4.2.2` is correctly installed - You can do so by executing `nvm install v4.2.2`.
If you need help setting up `nvm`, `npm` or if you got any other problems, join our [Slack](https://neos-project.slack.com/) channel and we are most happy to help you with it. :).__

## Building the assets

We rely on the NPM package manager and it's ecosystem for third party dependencies. We also use npm for building our assets.
As with every project which relies on NPM, execute `npm install` in the `src/Neos.NeosIo` directory first - This will fetch and
install all dependencies from the NPM registry. 

Afterwards you can run any of the following commands in your favorite shell.

### Commands

| Command         | Description                    |
| --------------- | ------------------------------ |
| `npm run build:modernizr` | Scans the whole project for modernizr references and builds a lean Modernizr file. |
| `npm run build:scripts` | Builds the JavaScript bundle with browserify and transpiles the ES6/7 code via babel. |
| `npm run fix:amd` | Fixes issues with the current Neos backend when using CommonJS. |
| `npm run build:sass` | Builds the Scss bundle with node-sass and autoprefixer. |
| `npm run build:styleguide` | Builds the living styleguide for all Scss components located in `Resources/Public/Styleguide/`. |
| `npm run build` | Higher-Order task for all above noted build tasks. |
| `npm run minify:scritpts` | Minifies all `*.js` files in `Resources/Public/JavaScript/`. |
| `npm run minify:styles` | Minifies all `*.css` files in `Resources/Public/Styles/`. |
| `npm run minify` | Higher-Order task for all above noted minify tasks. |
| `npm run lint:scripts` | Lints all `*.js` files in `Resources/Private/JavaScript/` via ESLint. |
| `npm run lint` | Higher-Order task for all above noted lint tasks. |
| `npm run watch:scripts` | Watches for file-changes and builds the JavaScript bundle on changes. |
| `npm run watch:sass` | Watches for file-changes and builds the Scss bundle on changes. |
| `npm run lint` | Higher-Order task for all above noted lint watch. |
| `npm run test` | Executes the lint task and in the future unit / end-to-end tests. |

## Structure and code style

You can find all css related sources in `Resources/Public/Styles/` and JavaScript related sources in `Resources/Public/JavaScript/`.

### (S)CSS guidelines

We use Atomic Design for structuring our (S)CSS code base. Atomic Design is basically an abstraction layer,
we do not aim at designing pages but components and a design system, which then will be used to create the pages.

If you aren't familiar with the structure, we recommend you to read [Atomic Web Design by Brad Frost](http://bradfrost.com/blog/post/atomic-web-design/).

### (S)CSS Styleguide

You can generate the css styleguide by executing `npm run build:styleguide` in your favorite shell. After the command has successfully finished, you can open `Resources/Public/Styleguide/index.html` in your favorite browser and take a look at all existing components and the respective markup. Documenting your own Atom, Molecule or Organism is pretty straight forward, take a look at their ['documenting css guide'](https://github.com/styledown/styledown/blob/master/docs/Documenting.md)

### JavaScript guidelines

We always strive for the most performant and modern code base, this also reflects in our JavaScript.
We use ES6 and some ES7 features like decorators, in corporation with [@reduct/component](https://github.com/reduct/component) and [@reduct/assembler](https://github.com/reduct/assembler)
which reduces the overall complexity and creates a lean and flexible system for creating abstract JavaScript components
with a scent of React's code style and logic.

**Some general rules:**

* Use ES6 features like `const`, `let`, arrow-functions, `import` and `exports` and so on. If you haven't worked with ES6 and need help, just create a PR and we will gladly point you in the right direction! :-)
* Do not create hard coded configuration values in your components. Use props/defaultProps instead.
* Do not rely on big frameworks/libraries like jQuery. Instead use the standarized Web API's like `classList` and so on. If you find yourself running into troubles with cross-browser optimization, check if there are polyfills for the given API available.
* Always bundle your dependencies and do not leak global variables on purpose - So no `<script>` tag other than the compiled bundle from browserify since you will run into async troubles and won't have code optimization that way.
* Orient yourself on the existing JS components and learn from then, if you have any problems in doing so, ping @inkdpixels or the #guild-website channel on [Slack](http://slack.neos.io/).

