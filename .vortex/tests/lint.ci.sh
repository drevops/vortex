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

echo "Checking that CircleCI steps do not bind-mount the working directory into a container."
# CircleCI jobs run against a remote Docker engine, so the engine resolves a
# bind mount of the working directory to an empty directory.
mounts="$(grep -n -E '(-v|--volume)[ =]"?[$]([{]?PWD|[(]pwd[)])' "${ROOT_DIR}/.circleci/config.yml")" || [ "$?" -eq 1 ]
if [ -n "${mounts}" ]; then
  echo "${mounts}"
  echo "These steps bind-mount the working directory, which the remote Docker engine sees as an empty directory. Copy the files in with 'docker cp' instead."
  exit 1
fi

# LCOV_EXCL_STOP
