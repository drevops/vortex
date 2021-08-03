#!/usr/bin/env bash
##
# Clean project build files.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

WEBROOT="${WEBROOT:-docroot}"

# ------------------------------------------------------------------------------

# Remove Drupal files, but preserve explicitly committed files.
targets=(
includes
misc
modules
profiles
scripts
sites/all/modules/contrib
sites/all/themes/contrib
sites/default/settings.generated.php
themes
)

# Explicitly reset permissions for sites/default.
chmod 755 "${WEBROOT}/sites/default"

# shellcheck disable=SC2207,SC2010
targets+=($(ls -p | grep -v /))
for target in "${targets[@]}"; do
  # Check if the target is not committed to git.
  if [ "$(git ls-files "${WEBROOT}/${target}")" == "" ]; then
    if [ -f "${WEBROOT}/${target:?}" ]; then chmod -Rf 777 "${WEBROOT}/${target:?}"; fi
    rm -Rf "${WEBROOT}/${target:?}"
  fi
done

# Remove other directories.
rm -rf \
  ./vendor \
  ./docroot/sites/all/themes/custom/*/build \
  ./docroot/sites/all/themes/custom/*/scss/_components.scss \
  ./docroot/sites/default/settings.generated.php

# shellcheck disable=SC2038
find . -type d -name node_modules | xargs rm -Rf
