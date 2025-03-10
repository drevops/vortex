#!/usr/bin/env bash
##
# Deploy code to a remote location.
#
# Deployment may include pushing code, pushing created container image, notifying
# remote hosting service via webhook call etc.
#
# Multiple deployments can be configured by providing a comma-separated list of
# deployment types in $VORTEX_DEPLOY_TYPES variable.
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
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
# @formatter:on

info "Started deployment."

[ -z "${VORTEX_DEPLOY_TYPES}" ] && fail "Missing required value for VORTEX_DEPLOY_TYPES. Must be a combination of comma-separated values (to support multiple deployments): code, container_registry, webhook, lagoon." && exit 1

if [ "${VORTEX_DEPLOY_ALLOW_SKIP:-}" = "1" ]; then
  note "Found flag to skip a deployment."

  if [ -n "${VORTEX_DEPLOY_PR}" ]; then
    # Allow skipping deployment by providing `$VORTEX_DEPLOY_SKIP_PR_<NUMBER>`
    # variable with value set to "1", where `<NUMBER>` is a PR number name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    #
    # Example:
    # For PR named 'pr-123', the variable name is $VORTEX_DEPLOY_SKIP_PR_123
    pr_skip_var="VORTEX_DEPLOY_SKIP_PR_${VORTEX_DEPLOY_PR}"
    if [ -n "${!pr_skip_var}" ]; then
      note "Found skip variable ${pr_skip_var} for PR ${VORTEX_DEPLOY_PR}."
      pass "Skipping deployment ${VORTEX_DEPLOY_TYPES}." && exit 0
    fi
  fi

  if [ -n "${VORTEX_DEPLOY_BRANCH:-}" ]; then
    # Allow skipping deployment by providing 'VORTEX_DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>'
    # variable with value set to "1", where <SAFE_BRANCH> is a branch name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    #
    # Example:
    # For 'main' branch, the variable name is $VORTEX_DEPLOY_SKIP_BRANCH_MAIN
    # For 'feature/my complex feature-123 update' branch, the variable name
    # is $VORTEX_DEPLOY_SKIP_BRANCH_MY_COMPLEX_FEATURE_123_UPDATE
    safe_branch_name="$(echo "${VORTEX_DEPLOY_BRANCH}" | tr -d '\n' | tr '[:space:]' '_' | tr '-' '_' | tr '/' '_' | tr -cd '[:alnum:]_' | tr '[:lower:]' '[:upper:]')"
    branch_skip_var="VORTEX_DEPLOY_SKIP_BRANCH_${safe_branch_name}"
    if [ -n "${!branch_skip_var:-}" ]; then
      note "Found skip variable ${branch_skip_var} for branch ${VORTEX_DEPLOY_BRANCH}."
      pass "Skipping deployment ${VORTEX_DEPLOY_TYPES}." && exit 0
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
