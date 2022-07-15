#!/usr/bin/env bash
##
# Run DrevOps tests in CI.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

[ "$(git config --global user.name)" = "" ] && echo "==> Configuring global git user name" && git config --global user.name "Test user"
[ "$(git config --global user.email)" = "" ] && echo "==> Configuring global git user email" && git config --global user.email "someone@example.com"

# Create stub of local framework.
docker network create amazeeio-network 2> /dev/null || true

# Run Composer Install deps to make sure that scripts run.
composer install --ignore-platform-reqs --no-interaction --prefer-source

echo "==> Run DrevOps unit tests."
composer install --ignore-platform-reqs --no-interaction --prefer-source
composer run post-install-cmd
pushd scripts/drevops/tests || exit 1
composer install -n --ansi
vendor/bin/phpunit unit
popd || exit 1

echo "==> Lint scripts code."
scripts/drevops/utils/lint-scripts.sh

echo "==> Check spelling."
scripts/drevops/utils/lint-spelling.sh

echo "==> Test BATS helpers."
bats scripts/drevops/tests/bats/helpers.bats --tap
bats scripts/drevops/tests/bats/helpers_drevops.bats --tap

echo "==> Test BATS mock."
bats scripts/drevops/tests/bats/mock.bats --tap

echo "==> Test installation."
bats scripts/drevops/tests/bats/env.bats --tap
bats scripts/drevops/tests/bats/docker-compose.bats --tap
bats scripts/drevops/tests/bats/drupal_install_site.bats --tap
bats scripts/drevops/tests/bats/install_initial.bats --tap
bats scripts/drevops/tests/bats/install_existing.bats --tap
bats scripts/drevops/tests/bats/install_parameters.bats --tap
bats scripts/drevops/tests/bats/install_integrations.bats --tap
bats scripts/drevops/tests/bats/install_demo.bats --tap
bats scripts/drevops/tests/bats/clean.bats --tap
bats scripts/drevops/tests/bats/update.bats --tap
