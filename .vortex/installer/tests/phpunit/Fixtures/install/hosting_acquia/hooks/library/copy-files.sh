#!/usr/bin/env bash
##
# Acquia Cloud hook: Copy files between environments.
#
# Environment variables must be set in Acquia UI globally or for each environment.

set -e
[ -n "${VORTEX_DEBUG}" ] && set -x

site="${1}"
target_env="${2}"

pushd "/var/www/html/${site}.${target_env}" >/dev/null || exit 1

[ "${VORTEX_TASK_COPY_FILES_ACQUIA_SKIP}" = "1" ] && echo "Skipping copying of files between Acquia environments." && exit 0

export VORTEX_ACQUIA_KEY="${VORTEX_ACQUIA_KEY?not set}"
export VORTEX_ACQUIA_SECRET="${VORTEX_ACQUIA_SECRET?not set}"
export VORTEX_ACQUIA_APP_NAME="${VORTEX_ACQUIA_APP_NAME:-${site}}"
export VORTEX_TASK_COPY_FILES_ACQUIA_SRC="${VORTEX_TASK_COPY_FILES_ACQUIA_SRC:-prod}"
export VORTEX_TASK_COPY_FILES_ACQUIA_DST="${VORTEX_TASK_COPY_FILES_ACQUIA_DST:-${target_env}}"

./scripts/vortex/task-copy-files-acquia.sh

popd >/dev/null || exit 1
