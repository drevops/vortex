#!/usr/bin/env bash
##
# Run DrevOps tests in CI.
#

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

SCRIPTS_DIR="scripts/drevops"

TEST_DIR="${SCRIPTS_DIR}/tests"

# ------------------------------------------------------------------------------

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2>/dev/null || true

echo "==> Lint scripts code."
"${TEST_DIR}/lint.scripts.sh"

echo "==> Check spelling."
"${TEST_DIR}/lint.spelling.sh"

echo "==> Lint dockerfiles."
"${TEST_DIR}/lint.dockerfiles.sh"

echo "==> Lint documentation."
pushd "${TEST_DIR}/../docs" || exit 1
sed -e "/###/d" docker-compose.yml >docker-compose.drevops_docs.yml
COMPOSE_FILE=docker-compose.drevops_docs.yml ahoy build
COMPOSE_FILE=docker-compose.drevops_docs.yml ahoy test
rm docker-compose.drevops_docs.yml >/dev/null
popd || exit 1

echo "==> Run installer unit tests."
pushd "${TEST_DIR}/../installer" || exit 1
if [ ! -d "./vendor" ]; then
  echo "  > Install Installer test Composer dependencies."
  composer install -n --ansi
fi
composer test
popd || exit 1

echo "==> Run common functional tests."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci

bats() {
  if [ -n "${DREVOPS_TEST_COVERAGE_DIR:-}" ]; then
    mkdir -p "${DREVOPS_TEST_COVERAGE_DIR}"
    kcov --include-pattern=.sh,.bash --include-path="${SCRIPTS_DIR}" --bash-parse-files-in-dir="${SCRIPTS_DIR}" --exclude-path=${TEST_DIR}/node_modules,${TEST_DIR}/vendor,${SCRIPTS_DIR}/installer/scripts/drevops/docs "${DREVOPS_TEST_COVERAGE_DIR}" "${TEST_DIR}/node_modules/.bin/bats" "$@"
  else
    "${TEST_DIR}/node_modules/.bin/bats" "$@"
  fi
}

bats "${TEST_DIR}/bats/helpers.bats"
bats "${TEST_DIR}/bats/helpers.run_steps.bats"
bats "${TEST_DIR}/bats/env.bats"
bats "${TEST_DIR}/bats/docker-compose.bats"
bats "${TEST_DIR}/bats/provision.bats"
bats "${TEST_DIR}/bats/notify.bats"
bats "${TEST_DIR}/bats/install.initial.bats"
bats "${TEST_DIR}/bats/install.existing.bats"
bats "${TEST_DIR}/bats/install.parameters.bats"
bats "${TEST_DIR}/bats/install.integrations.bats"
bats "${TEST_DIR}/bats/install.demo.bats"
bats "${TEST_DIR}/bats/clean.bats"
bats "${TEST_DIR}/bats/update-drevops.bats"
