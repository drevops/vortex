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
  echo "Generating .github/workflows/vortex-test-didi.yml."
  php "${ROOT_DIR}/.vortex/tests/generate-vortex-dev-gha"
else
  echo "Checking that .circleci/vortex-test-common.yml is up to date."
  php "${ROOT_DIR}/.vortex/tests/generate-vortex-dev-circleci" --check
  echo "Checking that .github/workflows/vortex-test-didi.yml is up to date."
  php "${ROOT_DIR}/.vortex/tests/generate-vortex-dev-gha" --check
fi

echo "Checking that both CI providers use the same database base image."
circleci_db_image_base="$(sed -n 's/^ *VORTEX_DB_IMAGE_BASE: *\([^ ]*\) *$/\1/p' "${ROOT_DIR}/.circleci/config.yml")"
gha_db_image_base="$(sed -n 's/.*VORTEX_DB_IMAGE_BASE=\([^" ]*\)".*/\1/p' "${ROOT_DIR}/.github/workflows/build-test-deploy.yml")"
if [ -z "${circleci_db_image_base}" ] || [ "${circleci_db_image_base}" != "${gha_db_image_base}" ]; then
  echo "VORTEX_DB_IMAGE_BASE differs between .circleci/config.yml (${circleci_db_image_base:-not set}) and .github/workflows/build-test-deploy.yml (${gha_db_image_base:-not set})."
  exit 1
fi

echo "Checking that CI steps do not bind-mount the working directory into a container."
# Every job in these files runs in a container or against a remote Docker
# engine, so the engine resolves a bind mount of the working directory to an
# empty directory.
ci_files=("${ROOT_DIR}/.circleci/config.yml" "${ROOT_DIR}/.github/workflows/build-test-deploy.yml" "${ROOT_DIR}/.github/workflows/audit.yml")
mounts="$(grep -n -E '(-v|--volume)[ =]"?[$]([{]?PWD|[(]pwd[)]|[{]?GITHUB_WORKSPACE)' "${ci_files[@]}")" || [ "$?" -eq 1 ]
if [ -n "${mounts}" ]; then
  echo "${mounts}"
  echo "These steps bind-mount the working directory, which the Docker engine sees as an empty directory. Copy the files in with 'docker cp' in CircleCI, or mount them with '--volumes-from \"\${HOSTNAME}\"' in GitHub Actions."
  exit 1
fi

# LCOV_EXCL_STOP
