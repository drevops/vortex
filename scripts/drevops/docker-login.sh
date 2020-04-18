#!/usr/bin/env bash
##
# Login to container registry.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# The username of the docker registry to download the database from.
DOCKER_REGISTRY_USERNAME="${DOCKER_REGISTRY_USERNAME:-}"
# The token of the docker registry to download the database from.
DOCKER_REGISTRY_TOKEN="${DOCKER_REGISTRY_TOKEN:-}"
# Docker registry name. Provide port, if required as <server_name>:<port>.
# Defaults to DockerHub.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

# ------------------------------------------------------------------------------

if [ -f "$HOME/.docker/config.json" ] && grep -q "${DOCKER_REGISTRY}" "$HOME/.docker/config.json"; then
  echo "==> Already logged in to registry \"${DOCKER_REGISTRY}\"."
elif [ -n "${DOCKER_REGISTRY_USERNAME}" ] &&  [ -n "${DOCKER_REGISTRY_TOKEN}" ]; then
  echo "==> Logging in to registry \"${DOCKER_REGISTRY}\"."
  echo "${DOCKER_REGISTRY_TOKEN}" | docker login --username "${DOCKER_REGISTRY_USERNAME}" --password-stdin "${DOCKER_REGISTRY}"
else
  echo "==> Skipping login into registry as either DOCKER_REGISTRY_USERNAME or DOCKER_REGISTRY_TOKEN was not provided."
fi
