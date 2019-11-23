#!/usr/bin/env bash
##
# Cloud hook: install site
#

set -e
set -x

# Create drush alias from arguments.
DRUSH_ALIAS="@$1.$2"

# Override configuration label.
DRUPAL_CONFIG_LABEL=vcs

# Skip DB import as it is managed through UI.
SKIP_DB_IMPORT=1

./scripts/drevops/drupal-install-site.sh
