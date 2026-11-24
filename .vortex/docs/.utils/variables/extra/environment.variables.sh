#!/usr/bin/env bash
##
# Additional environment variables discovered from the current environment.
# shellcheck disable=SC2034

# Docker Compose project name.
#
# Sets the project name for a Docker Compose project. Influences container and
# network names.
#
# Defaults to the name of the project directory.
COMPOSE_PROJECT_NAME=

# Override detected Drupal environment type.
#
# Used in the application to override the automatically detected environment type.
ENVIRONMENT_TYPE=

# Drupal hash salt.
#
# Secures one-time login links, password-reset URLs, and CSRF/form tokens. Set a
# long, random, unique value for every hosted environment as a platform variable
# or secret. If not set, a fallback is derived from the database host, which is
# suitable for local and CI use only.
#
# @see https://www.vortextemplate.com/docs/development/settings
DRUPAL_HASH_SALT="<generated from database host>"

# Path that replaces '/user' in every user route, such as '/user/login'.
#
# Set it as a platform variable on the hosting environment, so the path is not
# committed to the repository. Unset or empty keeps '/user'.
#
# @see https://www.vortextemplate.com/docs/development/modules/contributed-modules#rename-admin-paths
DRUPAL_USER_PATH=
