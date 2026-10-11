#!/usr/bin/env bash

rm -rf vendor/
composer install --no-dev

rsync -r --exclude '.*' \
	--exclude 'tests/' \
	--exclude '*.xml' \
	--exclude 'bin/' \
	--exclude 'assets/' \
	--exclude '*.zip' \
	--exclude 'compose.yml' \
	--exclude 'dockpress-secrets/' \
	. ../tengill-for-dk-svn/trunk

rsync -r "assets/" "../tengill-for-dk-svn/assets/"
