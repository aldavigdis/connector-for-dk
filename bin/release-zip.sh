#!/usr/bin/env bash

version=$(jq -r .version ./composer.json)

rm tengill-for-dk*.zip
rm -rf vendor/
composer install --no-dev

cd .. && zip -r tengill-for-dk.zip tengill-for-dk \
             -x tengill-for-dk/.git/\* \
			 tengill-for-dk/tests/*\* \
			 tengill-for-dk/*.xml \
			 tengill-for-dk/.* \
			 tengill-for-dk/.*\* \
			 tengill-for-dk/dockpress-secrets/\* \
			 tengill-for-dk/dockpress-secrets/ \
			 tengill-for-dk/bin/\* \
			 tengill-for-dk/bin/ \
			 tengill-for-dk/languages/*.*~ \
			 tengill-for-dk/assets/screenshot-*.png \
			 tengill-for-dk/static/

mv tengill-for-dk.zip "./tengill-for-dk/tengill-for-dk-pro-v$version.zip"
