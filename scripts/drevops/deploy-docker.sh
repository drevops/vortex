#!/usr/bin/env bash
##
# Deploy via pushing Docker images to Docker registry.
#
# This will push multiple docker images by tagging provided services in the
# DREVOPS_DEPLOY_DOCKER_MAP variable.
#

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Comma-separated map of docker services and images to use for deployment in
# format "service1=org/image1,service2=org/image2".
DREVOPS_DEPLOY_DOCKER_MAP="${DREVOPS_DEPLOY_DOCKER_MAP:-}"

# The username of the docker registry to deploy Docker image to.
DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME="${DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME:-${DREVOPS_DOCKER_REGISTRY_USERNAME}}"

# The token of the docker registry to deploy Docker image to.
DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN="${DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN:-${DREVOPS_DOCKER_REGISTRY_TOKEN}}"

# The registry of the docker registry to deploy Docker image to.
DREVOPS_DEPLOY_DOCKER_REGISTRY="${DREVOPS_DEPLOY_DOCKER_REGISTRY:-${DREVOPS_DOCKER_REGISTRY:-docker.io}}"

# The tag of the image to push to. Defaults to 'latest'.
DREVOPS_DEPLOY_DOCKER_IMAGE_TAG="${DREVOPS_DEPLOY_DOCKER_IMAGE_TAG:-latest}"

# ------------------------------------------------------------------------------

echo "==> Started DOCKER deployment."

# Only deploy if the map was provided, but do not fail if it has not as this
# may be called as a part of another task.
# @todo: Handle this better - empty DREVOPS_DEPLOY_DOCKER_MAP should use defaults.
[ -z "${DREVOPS_DEPLOY_DOCKER_MAP}" ] && echo "Services map is not specified in DREVOPS_DEPLOY_DOCKER_MAP variable. Docker deployment will not continue." && exit 0

services=()
images=()
# Parse and validate map.
IFS=',' read -r -a values <<< "${DREVOPS_DEPLOY_DOCKER_MAP}"
for value in "${values[@]}"; do
  IFS='=' read -r -a parts <<< "${value}"
  [ "${#parts[@]}" -ne 2 ] && echo "ERROR: invalid key/value pair \"${value}\" provided." && exit 1
  services+=("${parts[0]}")
  images+=("${parts[1]}")
done

# Login to the registry.
export DREVOPS_DOCKER_REGISTRY_USERNAME="${DREVOPS_DEPLOY_DOCKER_REGISTRY_USERNAME}"
export DREVOPS_DOCKER_REGISTRY_TOKEN="${DREVOPS_DEPLOY_DOCKER_REGISTRY_TOKEN}"
export DREVOPS_DOCKER_REGISTRY="${DREVOPS_DEPLOY_DOCKER_REGISTRY}"
./scripts/drevops/docker-login.sh

for key in "${!services[@]}"; do
  service="${services[$key]}"
  image="${images[$key]}"

  echo "  > Processing service ${service}."
  # Check if the service is running.)
  cid=$(docker-compose ps -q "${service}")

  [ -z "${cid}" ] && echo "ERROR: Service \"${service}\" is not running." && exit 1
  echo "  > Found \"${service}\" service container with id \"${cid}\"."

  [ -n "${image##*:*}" ] && image="${image}:${DREVOPS_DEPLOY_DOCKER_IMAGE_TAG}"
  new_image="${DREVOPS_DEPLOY_DOCKER_REGISTRY}/${image}"

  echo "  > Committing Docker image with name \"${new_image}\"."
  iid=$(docker commit "${cid}" "${new_image}")
  iid="${iid#sha256:}"
  echo "  > Committed Docker image with id \"${iid}\"."

  echo "  > Pushing Socker image to the registry."
  docker push "${new_image}"
done

echo "==> Finished DOCKER deployment."
