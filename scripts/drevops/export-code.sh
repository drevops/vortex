#!/usr/bin/env bash
##
# Export code artifact.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Directory to store exported code.
BUILD_EXPORT_DIR="${BUILD_EXPORT_DIR:-}"

# Path to application.
APP="${APP:-/app}"

# ------------------------------------------------------------------------------

if [ -n "${BUILD_EXPORT_DIR}" ]; then
  mkdir -p "${BUILD_EXPORT_DIR}"
  cp -R "${APP}"/. "${BUILD_EXPORT_DIR}"
  rm -Rf "${BUILD_EXPORT_DIR}"/node_modules >/dev/null;
fi
