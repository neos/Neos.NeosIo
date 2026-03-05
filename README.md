# Neos.NeosIo Rebrand

In a first step we are creating a new [presentational components](DistributionPackages/Neos.Presentation) used for the rebranding of the neos.io website.

## Setup & Installation

Clone the repository, and setup Neos as always. You can choose to use Local Beach, DDEV or your own setup.

### Local Beach setup
- Set up Local Beach as described here: https://www.flownative.com/en/documentation/guides/localbeach/local-beach-setup-docker-based-neos-development-howto.html
- Run `composer install`
- Run `beach start`
- Run `beach exec` to enter the container
- Inside the container run `./flow doctrine:migrate` and site imports etc. as needed
- add the following domains:
  - `./flow domain:add --site-node-name neosio --hostname neosio.localbeach.net --scheme https`
  - `./flow domain:add --site-node-name flowneosio --hostname flowneosio.localbeach.net --scheme https`
  - `./flow domain:add --site-node-name neosconio --hostname neosconio.localbeach.net --scheme https`
- To build css/js assets
  - Run `yarn` inside root of the project to install dependencies
  - Run `yarn build` to build the assets

_Note: We require [nvm](https://github.com/creationix/nvm#install-script) as well as the `yarn` binary to be installed on your system._

### DDEV setup
- Set up DDEV as described here: https://docs.ddev.com/en/stable/users/install/ddev-installation/
- Additional information about DDEV with Neos can be found here: https://docs.neos.io/guide/installation-development-setup/ddev-local-beach/all-platforms-using-ddev-and-docker
- Run `ddev start`
- You can call commands directly from outside with `ddev exec <command>`, but also enter the container with `ddev ssh`.
- There is also a convenience commands for 
  - Flow commands: `ddev flow <flow-command>`
  - Composer commands: `ddev composer <composer-command>`
  - NPM commands: `ddev npm <npm-command>`
  - Yarn commands: `ddev yarn <yarn-command>`
- Run `ddev composer install`
- Run `ddev flow doctrine:migrate` and site imports etc. as needed
- add the following domains:
  - `ddev flow domain:add --site-node-name neosio --hostname neos-io.ddev.site --scheme https`
  - `ddev flow domain:add --site-node-name flowneosio --hostname flow-neos-io.ddev.site --scheme https`
  - `ddev flow domain:add --site-node-name neosconio --hostname neoscon-io.ddev.site --scheme https`
  - `ddev flow domain:add --site-node-name barcamp --hostname barcamp-neos-io.ddev.site --scheme https`
- To build css/js assets
  - Run `ddev yarn` inside root of the project to install dependencies
  - Run `ddev yarn build` to build the assets


## Building the assets

### Commands

| Command         | Description                                       |
|-----------------|---------------------------------------------------|
| `yarn build`    | Builds all assets                                 |
| `yarn pipeline` | Runs install and then build all assets            |
| `yarn start`    | Watches the sources and rebuilds assets on change |


### Testing before live deployment

In addition to manual tests you should run the e2e tests before deploying to production:

```console
yarn test:e2e
```

You can set the environment variable `E2E_BASE_URL` to point to your local or staging instance if needed.

### Troubleshooting Performance Issues with Neos.IO

If you experience slow website behavior on your machine while using Neos.IO, follow these steps to check for potential solutions:

1. Docker Version: Ensure your Docker installation is up to date. Neos.IO requires Docker version 4.21 or above for optimal performance.

2. VirtioFS Status: Verify if VirtioFS is enabled in your Docker setup. VirtioFS provides improved I/O performance for operations on bind mounts, essential for Neos.IO to perform efficiently.
   * VirtioFS is available in macOS 12.5 and above. Make sure your macOS version meets this requirement. 
   * To enable VirtioFS and the Virtualization framework, check your Docker settings or configuration.

By following these steps, you can potentially address any performance-related issues with Neos.IO and enjoy a smoother website experience.
