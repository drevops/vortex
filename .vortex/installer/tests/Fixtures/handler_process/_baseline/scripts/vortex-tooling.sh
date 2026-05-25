#!/usr/bin/env bash
##
# Install 'drevops/vortex-tooling' into 'vendor/drevops/'.
#
# Host-side recipes (ahoy download-db, ahoy deploy, ahoy doctor, etc.) need
# the shipped Vortex shell scripts before the project's full 'composer install'
# has run. This script installs only that single package via a throwaway
# Composer project in 'vendor-temp/', so the project's main vendor/ and
# composer.lock are not touched.
#
# Idempotent: exits early if 'vendor/drevops/vortex-tooling/' already exists.
#
# Patches declared for 'drevops/vortex-tooling' in the project composer.json
# under 'extra.patches' (and the optional 'extra.patches-file') are copied
# over to the throwaway project and applied automatically by
# 'cweagans/composer-patches' during install.
#
# shellcheck disable=SC1090,SC1091

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Already installed - nothing to do.
if [ -d ./vendor/drevops/vortex-tooling ]; then
  exit 0
fi

mkdir -p vendor-temp vendor/drevops

# Authenticate Composer with GitHub if a token is available, to avoid hitting
# the anonymous API rate limit when downloading packages from GitHub.
if [ -n "${PACKAGE_TOKEN:-}" ]; then
  export COMPOSER_AUTH="{\"github-oauth\": {\"github.com\": \"${PACKAGE_TOKEN}\"}}"
fi

# Resolve the version constraint to install. Prefer the exact version from
# composer.lock; fall back to the constraint declared in composer.json.
version=
[ -f composer.lock ] && version=$(composer show drevops/vortex-tooling --locked 2>/dev/null | awk '/^versions/{print $NF}') || true
[ -z "${version}" ] && version=$(php -r 'echo json_decode(file_get_contents("composer.json"))->require->{"drevops/vortex-tooling"};')

# Bootstrap a throwaway Composer project that requires only the tooling.
echo "{\"require\":{\"drevops/vortex-tooling\":\"${version}\"}}" >vendor-temp/composer.json

# Carry over inline patches declared for our package, if any.
patches=$(composer config extra.patches.drevops/vortex-tooling --json 2>/dev/null) || patches=
if [ -n "${patches}" ] && [ "${patches}" != "[]" ] && [ "${patches}" != "{}" ]; then
  composer --working-dir=vendor-temp config extra.patches.drevops/vortex-tooling --json "${patches}"
fi

# Carry over the patches-file pointer, if defined. Prefix with '..' so the
# path resolves from inside 'vendor-temp/' back to the project root.
patches_file=$(composer config extra.patches-file 2>/dev/null) || patches_file=
if [ -n "${patches_file}" ]; then
  composer --working-dir=vendor-temp config extra.patches-file "../${patches_file}"
fi

# When any patches were registered, pull in the composer-patches plugin and
# allow it to run during install.
if [ -n "${patches}" ] || [ -n "${patches_file}" ]; then
  composer --working-dir=vendor-temp require --no-update cweagans/composer-patches:^2
  composer --working-dir=vendor-temp config allow-plugins.cweagans/composer-patches true
fi

composer --working-dir=vendor-temp install --no-dev --no-interaction

mv vendor-temp/vendor/drevops/vortex-tooling vendor/drevops/
rm -rf vendor-temp
