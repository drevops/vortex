#!/bin/bash
#
# Enable Shield Drupal module to help avoid indexing of UAT sites.
#

site="$1"
target_env="$2"

drush @$site.$target_env en shield -y
drush @$site.$target_env -y config-set shield.settings credentials.shield.user replace
drush @$site.$target_env -y config-set shield.settings credentials.shield.pass me
