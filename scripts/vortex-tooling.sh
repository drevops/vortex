#!/usr/bin/env bash
##
# Install the 'drevops/vortex-tooling' package into 'vendor/drevops/' so that
# shipped Vortex shell scripts are available on the host before a full
# 'composer install' has run.
#
# Idempotent: exits early if the package is already installed.
#
# The version constraint is read from 'composer.lock' (preferred, exact
# version) or 'composer.json' (fallback). A minimal 'composer.json' is created
# in a temporary working directory so only the tooling package is resolved,
# avoiding a full project install.
#
# shellcheck disable=SC1090,SC1091

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

if [ -d ./vendor/drevops/vortex-tooling ]; then
  exit 0
fi

mkdir -p vendor-temp vendor/drevops

version=
[ -f composer.lock ] && version=$(composer show drevops/vortex-tooling --locked 2>/dev/null | awk '/^versions/{print $NF}') || true
[ -z "${version}" ] && version=$(php -r 'echo json_decode(file_get_contents("composer.json"))->require->{"drevops/vortex-tooling"};')

echo "{\"require\":{\"drevops/vortex-tooling\":\"${version}\"}}" >vendor-temp/composer.json

#;< VORTEX_DEV
composer --working-dir=vendor-temp config repositories.vortex-tooling --json '{"type":"path","url":"../.vortex/tooling","options":{"symlink":false,"versions":{"drevops/vortex-tooling":"1.0.0"}}}'
#;> VORTEX_DEV

composer --working-dir=vendor-temp install --no-dev --no-scripts --no-plugins --no-interaction

mv vendor-temp/vendor/drevops/vortex-tooling vendor/drevops/
rm -rf vendor-temp
