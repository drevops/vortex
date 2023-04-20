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

# Name of the database fall-back docker image to use. If the image specified in
# DREVOPS_DB_DOCKER_IMAGE does not exist and base image was provided - it will
# be used as a "clean slate" for the database.
DREVOPS_DB_DOCKER_IMAGE_BASE=

# Skip copying of database between Acquia environments.
DREVOPS_TASK_COPY_DB_ACQUIA_SKIP=

# Skip copying of files between Acquia environments.
DREVOPS_TASK_COPY_FILES_ACQUIA_SKIP=

# Skip purging of edge cache in Acquia environments.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_SKIP=

# Skip Drupal site installation in Acquia environments.
DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP=

# Skip deployment email notification in Acquia environments.
DREVOPS_TASK_NOTIFY_DEPLOYMENT_EMAIL_ACQUIA_SKIP=

# New Relic availability flag.
NEWRELIC_ENABLED=

# New Relic license.
NEWRELIC_LICENSE=

# Proceed with Docker image deployment after it was exported.
DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED=

# Uncomment below to suppress Ahoy prompts.
AHOY_CONFIRM_RESPONSE=

# Print debug information in DrevOps scripts.
DREVOPS_DEBUG=

# Print debug information from Docker build.
DREVOPS_DOCKER_VERBOSE=

# Drupal admin email. May need to be reset if database was sanitized.
DREVOPS_DRUPAL_ADMIN_EMAIL="webmaster@your-site-url.example"

# Directory to store exported code.
DREVOPS_EXPORT_CODE_DIR=

# Flag to skip Email deployment notification.
DREVOPS_NOTIFY_DEPLOY_EMAIL_SKIP=
