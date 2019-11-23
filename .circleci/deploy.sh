#!/usr/bin/env bash
##
# Deploy code to a remote location.
#
# Deployment may include pushing code, pushing created docker image, notifying
# remote hosting service via webhook call etc.
#
# For deployment that require authentication, it is a good practice to create a
# separate Deployer user with own SSH key for every project. This allows to
# have independent non-personal account that will continue to work even if
# the developer who setup integrations leaves the project. It is also easier to
# restrict access or update SSH key of such account without affecting other
# projects.
#
# Add the following variables through CircleCI UI based on the type of deployment:
# - DEPLOY_TYPE - The type of deployment. Can be a combination of comma-separated
#   values (to support multiple deployments): code, docker, webhook.
# - DEPLOY_PROCEED - if the deployment should proceed. Useful for testing of the
#   CI config.
#
# For code deployment:
# - DEPLOY_GIT_USER_NAME - name of the user who will be committing to a remote
#   repository.
# - DEPLOY_GIT_USER_EMAIL - email address of the user who will be committing to
#   a remote repository.
# - DEPLOY_GIT_REMOTE - remote repository to push artifact to.
#
# For docker deployment:
# - DEPLOY_DOCKER_REGISTRY_URL - the URL of the docker registry.
# - DEPLOY_DOCKER_REGISTRY_USERNAME - the username to login to the docker registry.
# - DEPLOY_DOCKER_REGISTRY_PASSWORD - the password to login to the docker registry.
# - DEPLOY_DOCKER_REGISTRY_TOKEN - (optional) the authentication token to login
#   to the docker registry if tokens are used.
#
# For webhook deployment:
# - DEPLOY_WEBHOOK_URL - the URL of the webhook to call.
# - DEPLOY_WEBHOOK_RESPONSE_STATUS - the status code of the expected response.
#
# @see ./scripts/deploy.sh

set -e

#: Assigning values specific to CircleCI environment.
export DEPLOY_CODE_SRC="/workspace/code"
export DEPLOY_CODE_ROOT="$HOME/project"
export DEPLOY_REPORT="/tmp/artifacts/deployment_report.txt"

./scripts/deploy.sh
