#!/usr/bin/env bash
##
# Clean project build files.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

rm -rf \
  ./vendor \
  ./docroot/core \
  ./docroot/profiles/contrib \
  ./docroot/modules/contrib \
  ./docroot/themes/contrib \
  ./docroot/themes/custom/*/build \
  ./docroot/themes/custom/*/scss/_components.scss \
  ./docroot/sites/default/settings.generated.php

# shellcheck disable=SC2038
find . -type d -name node_modules | xargs rm -Rf
