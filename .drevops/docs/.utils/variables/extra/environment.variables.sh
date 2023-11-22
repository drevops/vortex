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
DRUPAL_ENVIRONMENT=
