#!/usr/bin/env bash
#
# Flush varnish cache for specified domains.
#
# Support sub-domains and custom domains.
# Place your domains into domains.txt file.
#

site="$1"
target_env="$2"
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DOMAINS_FILE=$DIR/../../library/domains.txt

while read domain; do
  TARGET_ENV=$target_env
  # Strip placeholder for PROD environment.
  if [ "$TARGET_ENV" == "prod" ] ; then
    domain=${domain//\$TARGET_ENV./}
  fi

  # Re-map 'test' to 'stg'.
  if [ "$TARGET_ENV" == "test" ] ; then
    TARGET_ENV=stg
  fi

  # Disable replacement for unknown environments.
  if [ "$TARGET_ENV" != "dev" ] && [ "$TARGET_ENV" == "stg" ] ; then
    TARGET_ENV=""
  fi

  # Proceed only if the environment was provided.
  if [ "$TARGET_ENV" != "" ] ; then
    # Interpolate variables in domain name.
    domain=$(eval echo $domain)

    # Clear varnish cache.
    drush @$site.$target_env ac-domain-purge $domain
  fi
done <$DOMAINS_FILE
