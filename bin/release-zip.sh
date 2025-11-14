#!/usr/bin/env bash

version=$(jq -r .version ./composer.json)

rm connector-for-dk*.zip
rm -rf vendor/
composer install --no-dev

cd .. && zip -r connector-for-dk.zip connector-for-dk \
             -x connector-for-dk/.git/\* connector-for-dk/.* connector-for-dk/.*\* connector-for-dk/dockpress-secrets/\*

mv connector-for-dk.zip "./connector-for-dk/connector-for-dk-pro-v$version.zip"
