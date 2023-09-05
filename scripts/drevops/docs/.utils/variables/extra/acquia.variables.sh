#!/usr/bin/env bash
##
# Additional environment variables used in this project in the Acquia environment.
# shellcheck disable=SC2034

# Skip copying of database between Acquia environment.
DREVOPS_TASK_COPY_DB_ACQUIA_SKIP=

# Skip copying of files between Acquia environment.
DREVOPS_TASK_COPY_FILES_ACQUIA_SKIP=

# Skip purging of edge cache in Acquia environment.
DREVOPS_TASK_PURGE_CACHE_ACQUIA_SKIP=

# Skip Drupal site installation in Acquia environment.
DREVOPS_TASK_DRUPAL_SITE_INSTALL_ACQUIA_SKIP=
