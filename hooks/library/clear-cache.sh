#!/usr/bin/env bash
set -e
set -x
#
# Cloud Hook: cache-clear
#
# Run drush cache-clear all in the target environment. This script works as
# any Cloud hook.

site="$1"
target_env="$2"

drush @$site.$target_env cc all
