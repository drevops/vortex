#!/usr/bin/env bash
##
# Login to container registry.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The username of the docker registry to download the database from.
DREVOPS_DOCKER_REGISTRY_USERNAME="${DREVOPS_DOCKER_REGISTRY_USERNAME:-}"
# The token of the docker registry to download the database from.
DREVOPS_DOCKER_REGISTRY_TOKEN="${DREVOPS_DOCKER_REGISTRY_TOKEN:-}"
# Docker registry name. Provide port, if required as <server_name>:<port>.
# Defaults to DockerHub.
DREVOPS_DOCKER_REGISTRY="${DREVOPS_DOCKER_REGISTRY:-docker.io}"

# ------------------------------------------------------------------------------

if [ -f "$HOME/.docker/config.json" ] && grep -q "${DREVOPS_DOCKER_REGISTRY}" "$HOME/.docker/config.json"; then
  echo "==> Already logged in to registry \"${DREVOPS_DOCKER_REGISTRY}\"."
elif [ -n "${DREVOPS_DOCKER_REGISTRY_USERNAME}" ] &&  [ -n "${DREVOPS_DOCKER_REGISTRY_TOKEN}" ]; then
  echo "==> Logging in to registry \"${DREVOPS_DOCKER_REGISTRY}\"."
  echo "${DREVOPS_DOCKER_REGISTRY_TOKEN}" | docker login --username "${DREVOPS_DOCKER_REGISTRY_USERNAME}" --password-stdin "${DREVOPS_DOCKER_REGISTRY}"
else
  echo "==> Skipping login into registry as either DREVOPS_DOCKER_REGISTRY_USERNAME or DREVOPS_DOCKER_REGISTRY_TOKEN was not provided."
fi
