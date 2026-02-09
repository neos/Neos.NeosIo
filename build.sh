#!/usr/bin/env bash

rm -f Web/robots.txt
yarn install
yarn pipeline
