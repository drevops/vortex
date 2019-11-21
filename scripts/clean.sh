#!/usr/bin/env bash
##
# Clean project build files.
#

set -e

rm -rf \
  ./vendor \
  ./node_modules \
  ./docroot/core \
  ./docroot/profiles/contrib \
  ./docroot/modules/contrib \
  ./docroot/themes/contrib \
  ./docroot/themes/custom/*/build \
  ./docroot/themes/custom/*/scss/_components.scss \
  ./docroot/sites/default/settings.generated.php
