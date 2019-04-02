#!/usr/bin/env bash
set -e
set -x
#
# Enable Shield Drupal module to help avoid indexing of UAT sites.
#

site="$1"
target_env="$2"

drush @$site.$target_env en shield -y
