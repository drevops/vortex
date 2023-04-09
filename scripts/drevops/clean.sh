#!/usr/bin/env bash
##
# Clean project build files.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# ------------------------------------------------------------------------------

rm -rf \
  "./vendor" \
  "./${DREVOPS_WEBROOT}/core" \
  "./${DREVOPS_WEBROOT}/profiles/contrib" \
  "./${DREVOPS_WEBROOT}/modules/contrib" \
  "./${DREVOPS_WEBROOT}/themes/contrib" \
  "./${DREVOPS_WEBROOT}/themes/custom/*/build" \
  "./${DREVOPS_WEBROOT}/themes/custom/*/scss/_components.scss" \
  "./${DREVOPS_WEBROOT}/sites/default/settings.generated.php"

# shellcheck disable=SC2038
find . -type d -name node_modules | xargs rm -Rf
