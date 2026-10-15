#!/usr/bin/env bash

rm -rf vendor/
composer install --no-dev

rsync -r --delete \
	--exclude '.*' \
	--exclude 'tests/' \
	--exclude '*.xml' \
	--exclude 'bin/' \
	--exclude 'assets/' \
	--exclude '*.zip' \
	--exclude '*.yml' \
	--exclude 'compose.yml' \
	--exclude 'dockpress-secrets/' \
	. ../tengill-for-dk-svn/trunk

rsync -r --delete "assets/" "../tengill-for-dk-svn/assets/"
