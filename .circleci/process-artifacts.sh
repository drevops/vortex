#!/usr/bin/env bash
##
# Process test artifacts.
#
set -e

# Copy from the app container to the build host for storage.
mkdir -p /tmp/artifacts/behat
docker cp "$(docker-compose ps -q cli)":/app/screenshots /tmp/artifacts/behat
