#!/usr/bin/env bash
# Fixture script to test mocking.
set -eu

curl -L -s -o /dev/null -w "%{http_code}" example.com

curl example.com
