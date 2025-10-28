#!/usr/bin/env bash

# Match the checksums listed in checksums.txt with those in the codebase.

sha512sum -c checksums.txt --quiet
