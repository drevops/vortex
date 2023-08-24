#!/usr/bin/env bash
##
# Clean project build files.
#
# IMPORTANT! This script runs outside the container on the host system.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# Name of the webroot directory with Drupal installation.
DREVOPS_WEBROOT="${DREVOPS_WEBROOT:-web}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started clean."

rm -rf \
  "./vendor" \
  "./${DREVOPS_WEBROOT}/core" \
  "./${DREVOPS_WEBROOT}/profiles/contrib" \
  "./${DREVOPS_WEBROOT}/modules/contrib" \
  "./${DREVOPS_WEBROOT}/themes/contrib" \
  "./${DREVOPS_WEBROOT}/themes/custom/*/build" \
  "./${DREVOPS_WEBROOT}/themes/custom/*/scss/_components.scss"

# shellcheck disable=SC2038
find . -type d -name node_modules | xargs rm -Rf

pass "Finished clean."
