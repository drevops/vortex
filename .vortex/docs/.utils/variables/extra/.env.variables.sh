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

# Skip building of the frontend.
VORTEX_FRONTEND_BUILD_SKIP=

# Password replacement used for sanitized database.
VORTEX_PROVISION_SANITIZE_DB_PASSWORD="<RANDOM STRING>"

# Container registry name.
#
# Provide port, if required as `<server_name>:<port>`.
VORTEX_CONTAINER_REGISTRY="${VORTEX_CONTAINER_REGISTRY:-docker.io}"

# Unblock admin account when logging in.
VORTEX_UNBLOCK_ADMIN=1

# Replace username with email after database sanitization. Useful when email
# is used as username.
VORTEX_PROVISION_SANITIZE_DB_REPLACE_USERNAME_WITH_EMAIL=0

# Drupal admin email. May need to be reset if database was sanitized.
DRUPAL_ADMIN_EMAIL=

# Drupal site name.
# Used only when installing from profile.
DRUPAL_SITE_NAME="${VORTEX_PROJECT}"

# Drupal site email.
# Used only when installing from profile.
DRUPAL_SITE_EMAIL="webmaster@your-site-domain.example"
