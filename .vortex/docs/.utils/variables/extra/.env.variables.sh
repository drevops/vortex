#!/usr/bin/env bash
##
# Additional environment variables used in this project, but commented-out in .env
#
# shellcheck disable=SC2034

# Name of the database container image to use.
#
# See https://github.com/drevops/mariadb-drupal-data to seed your DB image.
VORTEX_DB_IMAGE=

# Name of the database fall-back container image to use.
#
# If the image specified in $VORTEX_DB_IMAGE does not exist and base
# image was provided - it will be used as a "clean slate" for the database.
VORTEX_DB_IMAGE_BASE=

# Drupal admin email. May need to be reset if database was sanitized.
DRUPAL_ADMIN_EMAIL="webmaster@your-site-url.example"

# Password replacement used for sanitised database.
VORTEX_PROVISION_SANITIZE_DB_PASSWORD="<RANDOM STRING>"

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
VORTEX_CONTAINER_REGISTRY="${VORTEX_CONTAINER_REGISTRY:-docker.io}"

# Unblock admin account when logging in.
DRUPAL_UNBLOCK_ADMIN=1

# Drupal admin email. May need to be reset if database was sanitized.
DRUPAL_ADMIN_EMAIL=

# Replace username with email after database sanitization. Useful when email
# is used as username.
VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL=0

# Drupal site name.
# Used only when installing from profile.
DRUPAL_SITE_NAME="${VORTEX_PROJECT}"

# Drupal site email.
# Used only when installing from profile.
DRUPAL_SITE_EMAIL="webmaster@your-site-url.example"

# Print output from Composer install.
VORTEX_COMPOSER_VERBOSE=1

# Print output from NPM install.
VORTEX_NPM_VERBOSE=0

# Path to public files.
DRUPAL_PUBLIC_FILES="${DRUPAL_PUBLIC_FILES:-./${VORTEX_WEBROOT}/sites/default/files}"

# Path to private files.
DRUPAL_PRIVATE_FILES="${DRUPAL_PRIVATE_FILES:-${DRUPAL_PUBLIC_FILES}/private}"

# Path to temporary files.
DRUPAL_TEMPORARY_FILES="${DRUPAL_TEMPORARY_FILES:-${DRUPAL_PRIVATE_FILES}/tmp}"
