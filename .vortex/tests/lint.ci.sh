#!/usr/bin/env bash
##
# Lint Vortex CI configurations.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

FIX=0
for arg in "$@"; do
  [ "${arg}" = "--fix" ] && FIX=1
done

echo "==> Linting CI configurations in ${ROOT_DIR}."

if [ "${FIX}" = "1" ]; then
  echo "Generating .circleci/vortex-test-common.yml."
  php "${ROOT_DIR}/.vortex/tests/generate-vortex-dev-circleci"
else
  echo "Checking that .circleci/vortex-test-common.yml is up to date."
  php "${ROOT_DIR}/.vortex/tests/generate-vortex-dev-circleci" --check
fi
