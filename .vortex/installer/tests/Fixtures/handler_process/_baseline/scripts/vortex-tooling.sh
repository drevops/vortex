#!/usr/bin/env bash
##
# Install 'drevops/vortex-tooling' into 'vendor/drevops/' and link its binaries.
#
# Host-side recipes (ahoy fetch-db, ahoy deploy, ahoy doctor, etc.) need
# the shipped Vortex shell scripts before the project's full 'composer install'
# has run. This script installs only that single package via a throwaway
# Composer project in 'vendor-temp/', so the project's main vendor/ and
# composer.lock are not touched.
#
# Idempotent: exits early only once the package and its 'vendor/bin/vortex-*'
# proxies are both present.
#
# Patches declared for 'drevops/vortex-tooling' in the project composer.json
# under 'extra.patches' (and the optional 'extra.patches-file') are copied
# over to the throwaway project and applied automatically by
# 'cweagans/composer-patches' during install.
#
# shellcheck disable=SC1090,SC1091

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Already installed and its binaries linked - nothing to do.
if [ -d ./vendor/drevops/vortex-tooling ] && ls ./vendor/bin/vortex-* >/dev/null 2>&1; then
  exit 0
fi

mkdir -p vendor-temp vendor/drevops

# Always remove the throwaway project on exit - including when an intermediate
# step fails under 'set -e' - so a re-run never starts from a dirty state.
trap 'rm -rf vendor-temp' EXIT

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
  # Inline 'extra.patches' paths (and paths inside a 'patches-file') are
  # relative to the project root. Copy the project 'patches/' directory into
  # the throwaway project so those paths resolve from inside 'vendor-temp/'.
  if [ -d patches ]; then
    cp -R patches vendor-temp/
  fi
fi

composer --working-dir=vendor-temp install --no-dev --no-interaction

mv vendor-temp/vendor/drevops/vortex-tooling vendor/drevops/

# Expose the surfaced tooling binaries under 'vendor/bin/' too, so host-side
# recipes can invoke 'vendor/bin/vortex-*' before the project's full
# 'composer install' has generated them. The throwaway install created the bin
# proxies in 'vendor-temp/vendor/bin/'; their relative targets resolve once the
# proxies sit alongside the package under 'vendor/'.
if [ -d vendor-temp/vendor/bin ]; then
  mkdir -p vendor/bin
  for bin in vendor-temp/vendor/bin/vortex-*; do
    [ -e "${bin}" ] && mv "${bin}" vendor/bin/
  done
fi
