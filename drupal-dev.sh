#!/usr/bin/env bash
##
# Install Drupal-Dev files from the centralised location.
#
# Files already committed within current repository will not be overridden.
#
# To override any files coming from Drupal-Dev to persist in the current
# repository, modify `.git/info/exclude` file and commit them.
#
# Usage:
# source drupal-dev.sh
#
# To update all files, including committed:
# DRUPALDEV_ALLOW_OVERRIDE=1 source drupal-dev.sh
#

# Development only: uncomment and set the commit value to fetch Drupal-Dev at
# specific commit.
#export DRUPALDEV_COMMIT=COMMIT_SHA

curl -L https://raw.githubusercontent.com/integratedexperts/drupal-dev/8.x/install.sh | bash
