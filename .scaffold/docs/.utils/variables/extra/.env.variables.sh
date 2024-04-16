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
DRUPAL_ADMIN_EMAIL="webmaster@your-site-url.example"

# Password replacement used for sanitised database.
DREVOPS_PROVISION_SANITIZE_DB_PASSWORD="<RANDOM STRING>"

# Docker registry name.
#
# Provide port, if required as `<server_name>:<port>`.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

# Unblock admin account when logging in.
DRUPAL_UNBLOCK_ADMIN=1

# Drupal admin email. May need to be reset if database was sanitized.
DRUPAL_ADMIN_EMAIL=

# Replace username with email after database sanitization. Useful when email
# is used as username.
DREVOPS_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL=0

# Drupal site name.
# Used only when installing from profile.
DRUPAL_SITE_NAME="${DREVOPS_PROJECT}"

# Drupal site email.
# Used only when installing from profile.
DRUPAL_SITE_EMAIL="webmaster@your-site-url.example"

# Print output from Composer install.
DREVOPS_COMPOSER_VERBOSE=1

# Print output from NPM install.
DREVOPS_NPM_VERBOSE=0
