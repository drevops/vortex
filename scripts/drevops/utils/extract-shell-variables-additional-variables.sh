#!/usr/bin/env bash
##
# Additional environment variables used in this project, but not exposed
# explicitly or commented-out in .env.
#
# shellcheck disable=SC2034

# Docker Compose project name (all containers will have this name). Defaults
# to the name of the project directory.
COMPOSE_PROJECT_NAME=

# Lagoon project name. Uncomment if different from DREVOPS_PROJECT.
LAGOON_PROJECT=your_site

# Always override existing downloaded DB dump.
# Leave empty to always ask before overwriting existing DB dump.
DREVOPS_DB_DOWNLOAD_FORCE=

# Name of the database docker image to use. Uncomment to use an image with
# a DB data loaded into it.
# @see https://github.com/drevops/mariadb-drupal-data to seed your DB image.
DREVOPS_DB_DOCKER_IMAGE=your_org/your_site:latest

# Docker registry
DREVOPS_DOCKER_REGISTRY=docker.io

# Docker registry credentials to read and write Docker images.
# Note that for CI, these variables should be set through UI.
DREVOPS_DOCKER_REGISTRY_USERNAME=
DREVOPS_DOCKER_REGISTRY_TOKEN=
