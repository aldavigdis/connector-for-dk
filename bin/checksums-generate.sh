#!/usr/bin/env bash

# Re-calculate the checksums for all the files in the codebase and save it in
# checksums.txt.

rm checksums.txt
find . -path ./.git -prune -o -type f -exec sha512sum {} \; > checksums.txt
