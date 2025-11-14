#!/usr/bin/env bash

version=$(jq -r .version ./composer.json)

rm connector-for-dk*.zip
rm -rf vendor/
composer install --no-dev

cd .. && zip -r connector-for-dk.zip connector-for-dk \
             -x connector-for-dk/.git/\* \
			 connector-for-dk/tests/*\* \
			 connector-for-dk/*.xml \
			 connector-for-dk/.* \
			 connector-for-dk/.*\* \
			 connector-for-dk/dockpress-secrets/\* \
			 connector-for-dk/dockpress-secrets/ \
			 connector-for-dk/bin/\* \
			 connector-for-dk/bin/ \
			 connector-for-dk/languages/*.*~ \
			 connector-for-dk/assets/screenshot-*.png \
			 connector-for-dk/static/

mv connector-for-dk.zip "./connector-for-dk/connector-for-dk-pro-v$version.zip"
