#!/usr/bin/env bash

rm connector-for-dk.zip
rm -rf vendor/
composer install --no-dev

cd .. && zip -r connector-for-dk.zip connector-for-dk -i connector-for-dk/languages/\\*.php \
                                                      -i connector-for-dk/languages/\\*.mo \
                                                      -i connector-for-dk/languages/\\*.po \
                                                      -i connector-for-dk/languages/\\*.pot \
                                                      -i connector-for-dk/languages/\\*.json \
                                                      -i connector-for-dk/src/\\* \
                                                      -i connector-for-dk/vendor/\\* \
                                                      -i connector-for-dk/views/\\* \
                                                      -i connector-for-dk/js/\\* \
                                                      -i connector-for-dk/style/\\* \
                                                      -i connector-for-dk/*.php \
                                                      -i connector-for-dk/readme.txt \
                                                      -i connector-for-dk/assets/\\* \
                                                      -i connector-for-dk/composer.* \
                                                      -i connector-for-dk/*.xml \
                                                      -i connector-for-dk/readme.txt \
                                                      -i connector-for-dk/json_schemas/*.json

mv connector-for-dk.zip ./connector-for-dk/connector-for-dk.zip
