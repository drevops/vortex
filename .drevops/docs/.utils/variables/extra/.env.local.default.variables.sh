#!/usr/bin/env bash
##
# Additional environment variables used in this project, but commented-out in .env.local
#
# shellcheck disable=SC2034

# Local development URL.
# Override only if you need to use a different URL than the default.
DREVOPS_LOCALDEV_URL="<current_dir>.docker.amazee.io"

# Set to `1` to override existing downloaded DB dump without asking.
DREVOPS_DB_DOWNLOAD_FORCE=

# Set to `1` to print debug information in DrevOps scripts.
DREVOPS_DEBUG=

# Set to `1` to print debug information from Docker build.
DREVOPS_DOCKER_VERBOSE=

# Set to `y` to suppress Ahoy prompts.
AHOY_CONFIRM_RESPONSE=
