#!/usr/bin/env bash
##
# Build site in CI.
#
set -e

# Flag to validate composer lock.
COMPOSER_VALIDATE_LOCK=${COMPOSER_VALIDATE_LOCK:-1}

if [ "$COMPOSER_VALIDATE_LOCK" = "1" ]; then
  echo "==> Validate composer configuration, including lock file"
  composer validate --ansi --strict --no-check-all --no-check-lock
else
  echo "==> Validate composer configuration"
  composer validate --ansi --strict --no-check-all
fi

# Process Docker Compose configuration. This is used to avoid multiple
# docker-compose.yml files.
# Remove lines containing '###'.
sed -i -e "/###/d" docker-compose.yml
# Uncomment lines containing '##'.
sed -i -e "s/##//" docker-compose.yml

# Pull the latest images.
ahoy pull

# Build application.
export BUILD_EXPORT_DIR="/workspace/code"
ahoy build

# Create local settings.
.circleci/local-settings.sh
