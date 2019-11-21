#!/usr/bin/env bash
##
# Build site in CI.
#

set -e

# Flag to validate composer lock.
COMPOSER_VALIDATE_LOCK="${COMPOSER_VALIDATE_LOCK:-1}"

# ------------------------------------------------------------------------------

if [ "$COMPOSER_VALIDATE_LOCK" = "1" ]; then
  echo "==> Validate composer configuration, including lock file"
  composer validate --ansi --strict --no-check-all
else
  echo "==> Validate composer configuration"
  composer validate --ansi --strict --no-check-all --no-check-lock
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

# Skip sanitization during the build, but allow to override, if required.
export SKIP_SANITIZE_DB="${SKIP_SANITIZE_DB:-1}"

# Disable checks used on host machine.
export DOCTOR_CHECK_PYGMY=0
export DOCTOR_CHECK_PORT=0
export DOCTOR_CHECK_SSH=0
export DOCTOR_CHECK_WEBSERVER=0
export DOCTOR_CHECK_BOOTSTRAP=0

# Create stub of local framework.
docker network create amazeeio-network

ahoy build

# Create local settings.
.circleci/local-settings.sh
