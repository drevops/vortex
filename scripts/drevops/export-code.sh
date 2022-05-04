#!/usr/bin/env bash
##
# Export code artifact.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Directory to store exported code.
DREVOPS_BUILD_CODE_EXPORT_DIR="${DREVOPS_BUILD_CODE_EXPORT_DIR:-}"

# Path to application.
DREVOPS_APP="${APP:-/app}"

# ------------------------------------------------------------------------------

if [ -n "${DREVOPS_BUILD_CODE_EXPORT_DIR}" ]; then
  mkdir -p "${DREVOPS_BUILD_CODE_EXPORT_DIR}"
  cp -R "${DREVOPS_APP}"/. "${DREVOPS_BUILD_CODE_EXPORT_DIR}"
  rm -Rf "${DREVOPS_BUILD_CODE_EXPORT_DIR}"/node_modules >/dev/null;
fi
