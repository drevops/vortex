#!/usr/bin/env bash
##
# Additional environment variables used in this project, but commented-out in .env
#
# shellcheck disable=SC2034

# Name of the database docker image to use.
#
# See https://github.com/drevops/mariadb-drupal-data to seed your DB image.
DREVOPS_DB_DOCKER_IMAGE=

# Name of the database fall-back docker image to use.
#
# If the image specified in $DREVOPS_DB_DOCKER_IMAGE does not exist and base
# image was provided - it will be used as a "clean slate" for the database.
DREVOPS_DB_DOCKER_IMAGE_BASE=

# Drupal admin email. May need to be reset if database was sanitized.
DREVOPS_DRUPAL_ADMIN_EMAIL="webmaster@your-site-url.example"

# Password replacement used for sanitised database.
DREVOPS_DRUPAL_DB_SANITIZE_PASSWORD="<RANDOM STRING>"

# Docker registry name.
#
# Provide port, if required as `<server_name>:<port>`.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"
