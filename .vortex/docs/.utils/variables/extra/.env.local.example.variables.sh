#!/usr/bin/env bash
##
# Additional environment variables used in this project, but commented-out in .env.local
#
# shellcheck disable=SC2034

# Local development URL.
#
# Based on the `$COMPOSE_PROJECT_NAME` environment variable, which is set by
# Docker Compose to the name of the project directory.
#
# Override only if you need to use a different URL than the default.
VORTEX_LOCALDEV_URL="${COMPOSE_PROJECT_NAME:-example-site}.docker.amazee.io"

# Set to `1` to print debug information in Vortex scripts.
VORTEX_DEBUG=

# Set to `y` to suppress Ahoy prompts.
AHOY_CONFIRM_RESPONSE=

# When Ahoy prompts are suppressed ($AHOY_CONFIRM_RESPONSE is 1), the command
# will wait for 3 seconds before proceeding.
# Set this variable to "1" to skip the wait.
AHOY_CONFIRM_WAIT_SKIP=1
