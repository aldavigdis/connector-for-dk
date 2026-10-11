#!/usr/bin/env bash

version=$(jq -r .version ./composer.json)

bash ./bin/release-trunk.sh

rsync -r "../tengill-for-dk-svn/trunk/" "../tengill-for-dk-svn/tags/$version/"
