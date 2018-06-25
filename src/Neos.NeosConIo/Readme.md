# Neoscon.io website

## Setting up development

- `composer install` also installs this package
- we suggest to import the site using the CLI:
  - adjust `Configuration/Settings.yaml` with DB credentials; then run `./flow doctrine:migrate` (or use step 2 of the installer)
  - import the site using `./flow site:import --packageKey Neos.NeosConIo`
  - create an admin user using `./flow user:create --roles Administrator admin password My Admin`
  - log in using `http://127.0.0.1:8081/neos`

## Building SASS
To to a minified build of the SASS for production use, run the following command in the site package folder (where package.json is):

`npm run build:sass && npm run postbuild:sass && npm run minify:styles`

During development, you can use:

`npm run watch:sass`