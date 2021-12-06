#!/usr/bin/env bash
##
# Deploy code to a remote location.
#
# Deployment may include pushing code, pushing created docker image, notifying
# remote hosting service via webhook call etc.
#
# This is a router script to call relevant deployment scripts based on type.
#
# For required variables based on the deployment type,
# see ./scripts/drevops/deployment-[type].sh file.

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The type of deployment. Can be a combination of comma-separated values (to
# support multiple deployments): code, docker, webhook.
DEPLOY_TYPE="${DEPLOY_TYPE:-${1}}"

# Flag to proceed with deployment.
DEPLOY_PROCEED="${DEPLOY_PROCEED:-}"

# Flag to allow skipping of a deployment using additional flags.
# Different to DEPLOY_PROCEED in a way that DEPLOY_PROCEED is a failsafe
# to prevent any deployments, while DEPLOY_ALLOW_SKIP allows to selectively skip
# certain deployments using 'DEPLOY_SKIP_PR_<NUMBER>' and  'DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>'
# variables.
DEPLOY_ALLOW_SKIP="${DEPLOY_ALLOW_SKIP:-}"

# ------------------------------------------------------------------------------

[ -z "${DEPLOY_TYPE}" ] && echo "Missing required value for DEPLOY_TYPE. Must be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon." && exit 1

if [ "${DEPLOY_PROCEED}" != "1" ]; then
  echo "Skipping deployment ${DEPLOY_TYPE}." && exit 0
fi

if [ "${DEPLOY_ALLOW_SKIP}" == "1" ]; then
  echo "  > Found flag to allow skipping a deployment."

  if [ -n "${DEPLOY_PR}" ]; then
    # Allow to skip deployment by providing 'DEPLOY_SKIP_PR_<NUMBER>'
    # variable with value set to "1", where <NUMBER> is a PR number name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    # Example:
    # For 'pr-123' branch, the variable name is DEPLOY_SKIP_PR_123
    pr_skip_var="DEPLOY_SKIP_PR_${DEPLOY_PR}"
    if [ -n "${!pr_skip_var}" ]; then
      echo "  > Found skip variable $pr_skip_var for PR ${DEPLOY_PR}."
      echo "Skipping deployment ${DEPLOY_TYPE}." && exit 0
    fi
  fi

  if [ -n "${DEPLOY_BRANCH}" ]; then
    # Allow to skip deployment by providing 'DEPLOY_SKIP_BRANCH_<SAFE_BRANCH>'
    # variable with value set to "1", where <SAFE_BRANCH> is a branch name with
    # spaces, hyphens and forward slashes replaced with underscores and then
    # capitalised.
    # Example:
    # For 'master' branch, the variable name is DEPLOY_SKIP_BRANCH_MASTER
    # For 'feature/my complex feature-123 update' branch, the variable name
    # is DEPLOY_SKIP_BRANCH_MY_COMPLEX_FEATURE_123_UPDATE
    safe_branch_name="$(echo "${DEPLOY_BRANCH}" | tr -d '\n' | tr '[:space:]' '_' | tr '-' '_' | tr '/' '_' | tr -cd '[:alnum:]_' | tr '[:lower:]' '[:upper:]')"
    branch_skip_var="DEPLOY_SKIP_BRANCH_${safe_branch_name}"
    if [ -n "${!branch_skip_var}" ]; then
      echo "  > Found skip variable $branch_skip_var for branch ${DEPLOY_BRANCH}."
      echo "Skipping deployment ${DEPLOY_TYPE}." && exit 0
    fi
  fi
fi

if [ -z "${DEPLOY_TYPE##*code*}" ]; then
  echo "==> Starting 'code' deployment."
  ./scripts/drevops/deploy-code.sh
fi

if [ -z "${DEPLOY_TYPE##*webhook*}" ]; then
  echo "==> Starting 'webhook' deployment."
  ./scripts/drevops/deploy-webhook.sh
fi

if [ -z "${DEPLOY_TYPE##*docker*}" ]; then
  echo "==> Starting 'docker' deployment."
  ./scripts/drevops/deploy-docker.sh
fi

if [ -z "${DEPLOY_TYPE##*lagoon*}" ]; then
  echo "==> Starting 'lagoon' deployment."
  ./scripts/drevops/deploy-lagoon.sh
fi
