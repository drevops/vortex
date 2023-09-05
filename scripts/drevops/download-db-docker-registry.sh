#!/usr/bin/env bash
##
# Download DB dump from docker image.
#
# IMPORTANT! This script runs outside the container on the host system.
#

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -eu
[ "${DREVOPS_DEBUG-}" = "1" ] && set -x

# The Docker image containing database passed in a form of `<org>/<repository>`.
DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE:-}"

# The username of the docker registry to download the database from.
DOCKER_USER="${DOCKER_USER:-}"

# The token of the docker registry to download the database from.
DOCKER_PASS="${DOCKER_PASS:-}"

# Docker registry name.
# Provide port, if required as `<server_name>:<port>`.
DOCKER_REGISTRY="${DOCKER_REGISTRY:-docker.io}"

#-------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started Docker data image download."

[ -z "${DOCKER_USER}" ] && fail "Missing required value for DOCKER_USER." && exit 1
[ -z "${DOCKER_PASS}" ] && fail "Missing required value for DOCKER_PASS." && exit 1
[ -z "${DREVOPS_DB_DOCKER_IMAGE}" ] && fail "Destination image name is not specified. Please provide docker image name as a first argument to this script in a format <org>/<repository>." && exit 1

export DOCKER_USER="${DOCKER_USER}"
export DOCKER_PASS="${DOCKER_PASS}"
export DOCKER_REGISTRY="${DOCKER_REGISTRY}"
./scripts/drevops/docker-login.sh

docker pull "${DOCKER_REGISTRY}/${DREVOPS_DB_DOCKER_IMAGE}"

pass "Finished Docker data image download."
