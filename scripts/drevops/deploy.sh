#!/usr/bin/env bash
##
# Deploy code to a remote location.
#
# IMPORTANT! This script runs outside the container on the host system.
#
# Deployment may include pushing code, pushing created docker image, notifying
# remote hosting service via webhook call etc.
#
# This is a router script to call relevant scripts based on type.

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ -n "${DREVOPS_DEBUG:-}" ] && set -x

# The type of deployment.
#
# Can be a combination of comma-separated values (to support multiple
# deployments): code, docker, webhook, lagoon.
DREVOPS_DEPLOY_TYPE="${DREVOPS_DEPLOY_TYPE:-}"

# Deployment mode.
#
# Values can be one of: branch, tag.
DREVOPS_DEPLOY_MODE="${DREVOPS_DEPLOY_MODE:-branch}"

# Deployment action.
#
# Values can be one of: deploy, deploy_override_db, destroy.
DREVOPS_DEPLOY_ACTION="${DREVOPS_DEPLOY_ACTION:-deploy}"

# Deployment branch name.
DREVOPS_DEPLOY_BRANCH="${DREVOPS_DEPLOY_BRANCH:-}"

# Deployment pull request number without "pr-" prefix.
DREVOPS_DEPLOY_PR="${DREVOPS_DEPLOY_PR:-}"

# Flag to proceed with deployment. Set to 1 once the deployment configuration
# is configured in CI and is ready.
DREVOPS_DEPLOY_PROCEED="${DREVOPS_DEPLOY_PROCEED:-}"

# Flag to allow skipping of a deployment using additional flags.
#
# Different to $DREVOPS_DEPLOY_PROCEED in a way that $DREVOPS_DEPLOY_PROCEED is
# a failsafe to prevent any deployments, while $DREVOPS_DEPLOY_SKIP allows to
# selectively skip certain deployments using `$DREVOPS_DEPLOY_SKIP_PR_<NUMBER>'
# and `$DREVOPS_DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>` variables.
DREVOPS_DEPLOY_ALLOW_SKIP="${DREVOPS_DEPLOY_ALLOW_SKIP:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR:-}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started deployment."

[ -z "${DREVOPS_DEPLOY_TYPE}" ] && fail "Missing required value for DREVOPS_DEPLOY_TYPE. Must be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon." && exit 1

if [ "${DREVOPS_DEPLOY_PROCEED}" != "1" ]; then
  echo "DREVOPS_DEPLOY_PROCEED is not set to 1." && pass "Skipping deployment ${DREVOPS_DEPLOY_TYPE}." && exit 0
fi

if [ "${DREVOPS_DEPLOY_ALLOW_SKIP}" = "1" ]; then
  note "Found flag to skip a deployment."

  if [ -n "${DREVOPS_DEPLOY_PR}" ]; then
    # Allow skipping deployment by providing `$DREVOPS_DEPLOY_SKIP_PR_<NUMBER>`
    # variable with value set to "1", where `<NUMBER>` is a PR number name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    #
    # Example:
    # For PR named 'pr-123', the variable name is $DREVOPS_DEPLOY_SKIP_PR_123
    pr_skip_var="DREVOPS_DEPLOY_SKIP_PR_${DREVOPS_DEPLOY_PR}"
    if [ -n "${!pr_skip_var}" ]; then
      note "Found skip variable ${pr_skip_var} for PR ${DREVOPS_DEPLOY_PR}."
      pass "Skipping deployment ${DREVOPS_DEPLOY_TYPE}." && exit 0
    fi
  fi

  if [ -n "${DREVOPS_DEPLOY_BRANCH}" ]; then
    # Allow skipping deployment by providing 'DREVOPS_DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>'
    # variable with value set to "1", where <SAFE_BRANCH> is a branch name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    #
    # Example:
    # For 'main' branch, the variable name is $DREVOPS_DEPLOY_SKIP_BRANCH_MAIN
    # For 'feature/my complex feature-123 update' branch, the variable name
    # is $DREVOPS_DEPLOY_SKIP_BRANCH_MY_COMPLEX_FEATURE_123_UPDATE
    safe_branch_name="$(echo "${DREVOPS_DEPLOY_BRANCH}" | tr -d '\n' | tr '[:space:]' '_' | tr '-' '_' | tr '/' '_' | tr -cd '[:alnum:]_' | tr '[:lower:]' '[:upper:]')"
    branch_skip_var="DREVOPS_DEPLOY_SKIP_BRANCH_${safe_branch_name}"
    if [ -n "${!branch_skip_var:-}" ]; then
      note "Found skip variable ${branch_skip_var} for branch ${DREVOPS_DEPLOY_BRANCH}."
      pass "Skipping deployment ${DREVOPS_DEPLOY_TYPE}." && exit 0
    fi
  fi
fi

if [ -z "${DREVOPS_DEPLOY_TYPE##*artifact*}" ]; then
  [ "${DREVOPS_DEPLOY_MODE}" = "tag" ] && export DREVOPS_DEPLOY_ARTIFACT_DST_BRANCH="deployment/[tags:-]"
  ./scripts/drevops/deploy-artifact.sh
fi

if [ -z "${DREVOPS_DEPLOY_TYPE##*webhook*}" ]; then
  ./scripts/drevops/deploy-webhook.sh
fi

if [ -z "${DREVOPS_DEPLOY_TYPE##*docker*}" ]; then
  ./scripts/drevops/deploy-docker.sh
fi

if [ -z "${DREVOPS_DEPLOY_TYPE##*lagoon*}" ]; then
  ./scripts/drevops/deploy-lagoon.sh
fi
