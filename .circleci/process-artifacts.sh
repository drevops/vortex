#!/usr/bin/env bash
##
# Process test artifacts.
#
set -e

# Create screenshots directory in case it was not created before. This is to
# avoid this script to fail when copying artifacts.
ahoy cli "mkdir -p /app/screenshots"

# Copy from the app container to the build host for storage.
mkdir -p /tmp/artifacts/behat
docker cp "$(docker-compose ps -q cli)":/app/screenshots /tmp/artifacts/behat
