#!/usr/bin/env bash
set -e
set -x
#
# Flush varnish cache for specified domains.
#
# Support sub-domains and custom domains.
# Place your domains into domains.txt file.
#
# IMPORTANT! This script uses drush ac-* commands and requires credentials
# for Acquia Cloud. Make sure that file "$HOME/.acquia/cloudapi.conf" exists and
# follow deployment instructions if it does not.

site="$1"
target_env="$2"
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DOMAINS_FILE=$DIR/../../library/domains.txt

[ ! -f "$DOMAINS_FILE" ] && echo "ERROR: File with domains does not exist." && exit 1
[ ! -f "$HOME/.acquia/cloudapi.conf" ] && echo "ERROR: Acquia Cloud API credentials file $HOME/.acquia/cloudapi.conf does not exist." && exit 1

while read domain; do
  TARGET_ENV=$target_env
  # Strip placeholder for PROD environment.
  if [ "$TARGET_ENV" == "prod" ] ; then
    domain=${domain//\$TARGET_ENV./}
  fi

  # Re-map 'test' to 'stg'.
  if [ "$TARGET_ENV" == "test" ] ; then
    TARGET_ENV=stage
  fi

  # Disable replacement for unknown environments.
  if [ "$TARGET_ENV" != "dev" ] && [ "$TARGET_ENV" != "stage" ] ; then
    TARGET_ENV=""
  fi

  # Proceed only if the environment was provided.
  if [ "$TARGET_ENV" != "" ] ; then
    # Interpolate variables in domain name.
    domain=$(eval echo $domain)

    # Clear varnish cache.
    echo drush @$site.$target_env ac-domain-purge $domain
    drush @$site.$target_env ac-domain-purge $domain || true
  fi
done <$DOMAINS_FILE
