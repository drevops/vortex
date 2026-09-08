#!/usr/bin/env bash
##
# Acquia Cloud hook: Copy files between environments.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

site="${1:?Missing required site name.}"
target_env="${2:?Missing required target environment name.}"

[ "${VORTEX_TASK_COPY_FILES_ACQUIA_SKIP:-}" = "1" ] && echo "Skipping copying of files between Acquia environments." && exit 0

export VORTEX_PLATFORM=acquia
export VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY:?Missing required value.}"
export VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET:?Missing required value.}"
export VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-${site}}"
export VORTEX_TASK_COPY_FILES_ACQUIA_SRC="${VORTEX_TASK_COPY_FILES_ACQUIA_SRC:-prod}"
export VORTEX_TASK_COPY_FILES_ACQUIA_DST="${VORTEX_TASK_COPY_FILES_ACQUIA_DST:-${target_env}}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

./vendor/bin/vortex-task copy-files

popd >/dev/null || exit 1
