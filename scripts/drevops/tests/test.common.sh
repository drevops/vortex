#!/usr/bin/env bash
##
# Run DrevOps tests in CI.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

TEST_DIR="scripts/drevops/tests"

# Create stub of local framework.
docker network create amazeeio-network 2> /dev/null || true

# Configure git username and email if it is not set.
[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

echo "==> Run unit tests."
if [ ! -d "./vendor" ]; then
  echo "  > Install root Composer dependencies."
  composer install --ignore-platform-reqs --no-interaction --prefer-source
  composer run post-install-cmd
fi

pushd "${TEST_DIR}" || exit 1
if [ ! -d "./vendor" ]; then
  echo "  > Install test Composer dependencies."
  composer install -n --ansi
fi
vendor/bin/phpunit unit
popd || exit 1

echo "==> Lint scripts code."
"${TEST_DIR}/lint-scripts.sh"

echo "==> Check spelling."
"${TEST_DIR}/lint-spelling.sh"

echo "==> Lint dockerfiles."
"${TEST_DIR}/lint-dockerfiles.sh"

echo "==> Run common functional tests."
[ ! -d "${TEST_DIR}/node_modules" ] && echo "  > Install test Node dependencies." && npm --prefix="${TEST_DIR}" ci
bats="${TEST_DIR}/node_modules/.bin/bats"

$bats "${TEST_DIR}/bats/helpers.bats"
$bats "${TEST_DIR}/bats/env.bats"
$bats "${TEST_DIR}/bats/docker-compose.bats"
$bats "${TEST_DIR}/bats/drupal_install_site.bats"
$bats "${TEST_DIR}/bats/install_initial.bats"
$bats "${TEST_DIR}/bats/install_existing.bats"
$bats "${TEST_DIR}/bats/install_parameters.bats"
$bats "${TEST_DIR}/bats/install_integrations.bats"
$bats "${TEST_DIR}/bats/install_demo.bats"
$bats "${TEST_DIR}/bats/clean.bats"
$bats "${TEST_DIR}/bats/update.bats"
