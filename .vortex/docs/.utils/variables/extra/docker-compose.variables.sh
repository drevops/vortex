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
#
# Variable is not used in hosting environment.
DATABASE_HOST=database

# Local database name.
#
# Variable is not used in hosting environment.
DATABASE_NAME=drupal

# Local database user.
#
# Variable is not used in hosting environment.
DATABASE_USERNAME=drupal

# Local database password.
#
# Variable is not used in hosting environment.
DATABASE_PASSWORD=drupal

# Local database port.
#
# Variable is not used in hosting environment.
DATABASE_PORT=3306

# Local database charset.
#
# Variable is not used in hosting environment.
DATABASE_CHARSET=utf8mb4

# Local database collation.
#
# Variable is not used in hosting environment.
DATABASE_COLLATION=utf8mb4_general_ci
