#!/usr/bin/env bash
##
# Acquia Cloud hook: Install site.
#

set -e
set -x

# Create drush alias from arguments.
export DRUSH_ALIAS="@${1}.${2}"

# Skip DB import as it is managed through UI.
export SKIP_DB_IMPORT=1

./scripts/drevops/drupal-install-site.sh
