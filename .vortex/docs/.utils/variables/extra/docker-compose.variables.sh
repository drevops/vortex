#!/usr/bin/env bash
##
# Additional environment variables used in this project in docker-compose.yml
#
# shellcheck disable=SC2034

# Path to public files.
DRUPAL_PUBLIC_FILES="${DRUPAL_PUBLIC_FILES:-./${WEBROOT}/sites/default/files}"

# Path to private files.
DRUPAL_PRIVATE_FILES="${DRUPAL_PRIVATE_FILES:-${DRUPAL_PUBLIC_FILES}/private}"

# Path to temporary files.
DRUPAL_TEMPORARY_FILES="${DRUPAL_TEMPORARY_FILES:-${DRUPAL_PRIVATE_FILES}/tmp}"

# Local database host.
DATABASE_HOST=database

# Local database name.
DATABASE_NAME=drupal

# Local database user.
DATABASE_USERNAME=drupal

# Local database password.
DATABASE_PASSWORD=drupal

# Local database port.
DATABASE_PORT=3306
