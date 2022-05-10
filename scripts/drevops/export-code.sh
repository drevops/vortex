#!/usr/bin/env bash
##
# Export code.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Path to application.
DREVOPS_APP="${DREVOPS_APP:-/app}"

# Directory to store exported code.
DREVOPS_EXPORT_CODE_DIR="${DREVOPS_EXPORT_CODE_DIR:-}"

# ------------------------------------------------------------------------------

if [ -n "${DREVOPS_EXPORT_CODE_DIR}" ]; then
  mkdir -p "${DREVOPS_EXPORT_CODE_DIR}"
  cp -R "${DREVOPS_APP}"/. "${DREVOPS_EXPORT_CODE_DIR}"
  rm -Rf "${DREVOPS_EXPORT_CODE_DIR}"/node_modules >/dev/null;
fi
