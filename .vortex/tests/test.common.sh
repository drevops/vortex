#!/usr/bin/env bash
##
# Run Vortex tests in CI.
#
# LCOV_EXCL_START

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

ROOT_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)")"

SCRIPTS_DIR="${ROOT_DIR}/.vortex/tooling/src"

TOOLING_DIR="${ROOT_DIR}/.vortex/tooling"

BATS_DIR="${TOOLING_DIR}/tests"

# ------------------------------------------------------------------------------

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global test git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global test git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2>/dev/null || true

echo "==> Run common functional tests."
[ ! -d "${TOOLING_DIR}/node_modules" ] && echo "  > Install tooling Node dependencies." && yarn --cwd="${TOOLING_DIR}" install --frozen-lockfile

bats() {
  pushd "${ROOT_DIR}" >/dev/null || exit 1
  "${TOOLING_DIR}/node_modules/.bin/bats" "$@"
  popd >/dev/null || exit 1
}

# Runs each test file under a separate kcov process and merges the reports.
# A single kcov process over the whole suite grows until the runner kills it.
bats_coverage() {
  local dir="${1}"
  local parts_dir="${VORTEX_DEV_TEST_COVERAGE_DIR}-parts"
  local status=0
  local file

  pushd "${ROOT_DIR}" >/dev/null || exit 1
  rm -rf "${parts_dir}"
  mkdir -p "${VORTEX_DEV_TEST_COVERAGE_DIR}" "${parts_dir}"

  for file in "${dir}"/*.bats; do
    kcov --include-pattern=.sh,.bash --bash-parse-files-in-dir="${SCRIPTS_DIR}","${BATS_DIR}" --exclude-pattern=vendor,node_modules "${parts_dir}/$(basename "${file}" .bats)" "${TOOLING_DIR}/node_modules/.bin/bats" "${file}" || status=1
  done

  kcov --merge "${VORTEX_DEV_TEST_COVERAGE_DIR}" "${parts_dir}"/*
  popd >/dev/null || exit 1

  return "${status}"
}

if [ -n "${VORTEX_DEV_TEST_COVERAGE_DIR:-}" ]; then
  bats_coverage "${BATS_DIR}/unit"
else
  bats "${BATS_DIR}/unit"
fi

# LCOV_EXCL_STOP
