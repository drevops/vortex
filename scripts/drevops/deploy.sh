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

# ------------------------------------------------------------------------------

[ -z "${DEPLOY_TYPE}" ] && echo "Missing required value for DEPLOY_TYPE. Must be a combination of comma-separated values (to support multiple deployments): code, docker, webhook, lagoon." && exit 1

if [ -z "${DEPLOY_PROCEED}" ]; then
  echo "Skipping deployment ${DEPLOY_TYPE}." && exit 0
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
