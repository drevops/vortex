#!/usr/bin/env bash
##
# Build site in CI.
#
set -e

if [ "$COMPOSER_VALIDATE_LOCK" = "1" ]; then
  echo "==> Validate composer configuration, including lock file"
  composer validate --ansi --strict --no-check-all --no-check-lock
else
  echo "==> Validate composer configuration"
  composer validate --ansi --strict --no-check-all
fi

# Process Docker Compose configuration. This is used to avoid multiple
# docker-compose.yml files.
sed -i -e "/###/d" docker-compose.yml
sed -i -e "s/##//" docker-compose.yml


#      - run:
#          name: Check DB availability
#          command: if [ ! -f .data/db.sql ] ; then echo "Unable to find DB"; exit 1; fi
#

# Pull the latest images.
ahoy pull

# Build application.
export BUILD_EXPORT_DIR="/workspace/code"
ahoy build

# Create local settings.
.circleci/local-settings.sh
