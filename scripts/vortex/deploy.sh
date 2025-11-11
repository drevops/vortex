#!/usr/bin/env bash
##
# Deploy code to a remote location.
#
# Deployment may include pushing code, pushing created container image,
# notifying remote hosting service via webhook call etc.
#
# Multiple deployments can be configured by providing a comma-separated list of
# deployment types in $VORTEX_DEPLOY_TYPES variable.
#
# Deployments can be skipped by setting the $VORTEX_DEPLOY_SKIP variable to "1".
#
# This is a router script to call relevant scripts based on type.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# shellcheck disable=SC1090,SC1091

t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "${t}" && rm "${t}" && unset t

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# The types of deployment.
#
# Can be a combination of comma-separated values (to support multiple
# deployments): code, container_registry, webhook, lagoon.
VORTEX_DEPLOY_TYPES="${VORTEX_DEPLOY_TYPES:-}"

# Deployment mode.
#
# Values can be one of: branch, tag.
VORTEX_DEPLOY_MODE="${VORTEX_DEPLOY_MODE:-branch}"

# Deployment action.
#
# Values can be one of: deploy, deploy_override_db, destroy.
# - deploy: Deploy code and preserve database in the environment.
# - deploy_override_db: Deploy code and override database in the environment.
# - destroy: Destroy the environment (if the provider supports it).
VORTEX_DEPLOY_ACTION="${VORTEX_DEPLOY_ACTION:-}"

# Deployment branch name.
VORTEX_DEPLOY_BRANCH="${VORTEX_DEPLOY_BRANCH:-}"

# Deployment pull request number without "pr-" prefix.
VORTEX_DEPLOY_PR="${VORTEX_DEPLOY_PR:-}"

# Flag to allow skipping of a deployment using additional flags.
VORTEX_DEPLOY_ALLOW_SKIP="${VORTEX_DEPLOY_ALLOW_SKIP:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started deployment."

if [ "${VORTEX_DEPLOY_SKIP:-}" = "1" ]; then
  note "Found flag to skip all deployments."
  pass "Skipping deployment ${VORTEX_DEPLOY_TYPES}."
  exit 0
fi

[ -z "${VORTEX_DEPLOY_TYPES}" ] && fail "Missing required value for VORTEX_DEPLOY_TYPES. Must be a combination of comma-separated values (to support multiple deployments): code, container_registry, webhook, lagoon." && exit 1

if [ "${VORTEX_DEPLOY_ALLOW_SKIP:-}" = "1" ]; then
  note "Found flag to skip a deployment."

  if [ -n "${VORTEX_DEPLOY_PR}" ] && [ -n "${VORTEX_DEPLOY_SKIP_PRS:-}" ]; then
    # Allow skipping deployment by providing `$VORTEX_DEPLOY_SKIP_PRS` variable
    # with PR numbers as a single value or comma-separated list.
    #
    # Examples:
    # VORTEX_DEPLOY_SKIP_PRS=123
    # VORTEX_DEPLOY_SKIP_PRS=123,456,789
    if echo ",${VORTEX_DEPLOY_SKIP_PRS}," | grep -q ",${VORTEX_DEPLOY_PR},"; then
      note "Found PR ${VORTEX_DEPLOY_PR} in skip list."
      note "Skipping deployment ${VORTEX_DEPLOY_TYPES}."
      exit 0
    fi
  fi

  if [ -n "${VORTEX_DEPLOY_BRANCH:-}" ] && [ -n "${VORTEX_DEPLOY_SKIP_BRANCHES:-}" ]; then
    # Allow skipping deployment by providing `$VORTEX_DEPLOY_SKIP_BRANCHES`
    # variable with branch names as a single value or comma-separated list.
    #
    # Branch names must match exactly as they appear in the repository.
    #
    # Examples:
    # VORTEX_DEPLOY_SKIP_BRANCHES=feature/test
    # VORTEX_DEPLOY_SKIP_BRANCHES=feature/test,hotfix/urgent,project/experimental
    if echo ",${VORTEX_DEPLOY_SKIP_BRANCHES}," | grep -qF ",${VORTEX_DEPLOY_BRANCH},"; then
      note "Found branch ${VORTEX_DEPLOY_BRANCH} in skip list."
      note "Skipping deployment ${VORTEX_DEPLOY_TYPES}."
      exit 0
    fi
  fi
fi

if [ -z "${VORTEX_DEPLOY_TYPES##*artifact*}" ]; then
  [ "${VORTEX_DEPLOY_MODE}" = "tag" ] && export VORTEX_DEPLOY_ARTIFACT_DST_BRANCH="deployment/[tags:-]"
  ./scripts/vortex/deploy-artifact.sh
fi

if [ -z "${VORTEX_DEPLOY_TYPES##*webhook*}" ]; then
  ./scripts/vortex/deploy-webhook.sh
fi

if [ -z "${VORTEX_DEPLOY_TYPES##*container_registry*}" ]; then
  ./scripts/vortex/deploy-container-registry.sh
fi

if [ -z "${VORTEX_DEPLOY_TYPES##*lagoon*}" ]; then
  ./scripts/vortex/deploy-lagoon.sh
fi
