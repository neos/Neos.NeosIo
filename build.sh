#!/usr/bin/env bash

rm -f Web/robots.txt
cd DistributionPackages/Neos.NeosConIo
npm install
npm run build:sass
npm run minify:styles
cd -
cd Packages/Application/Neos.MarketPlace
npm install
npm run build
npm run minify
cd -
cd DistributionPackages/Neos.NeosIo
npm install
npm run build
npm run minify
cd -