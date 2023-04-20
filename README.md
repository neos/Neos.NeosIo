# Neos.NeosIo

The official neos.io website package.

## Setup & Installation

Clone the repository, and setup Neos as always:

- Set up local beach as described here: https://www.flownative.com/en/documentation/guides/localbeach/local-beach-setup-docker-based-neos-development-howto.html
- Run `composer install`
- Run `beach start`
- Run `beach exec` to enter the container
- Inside the container run `./flow doctrine:migrate` and site imports etc. as needed

_Note: We require [nvm](https://github.com/creationix/nvm#install-script) as well as the `yarn` binary to be installed on your system._

## Building the assets

### Commands

| Command         | Description                    |
| --------------- | ------------------------------ |
| `yarn build` | Builds all assets |
| `yarn pipeline` | Runs install and then build all assets |
| `yarn start` | Watches the sources and rebuilds assets on change |
