#!/usr/bin/env bash
##
# Additional environment variables used in this project, but commented-out in .env.local
#
# shellcheck disable=SC2034

# Local development URL.
# Override only if you need to use a different URL than the default.
VORTEX_LOCALDEV_URL="<current_dir>.docker.amazee.io"

# Set to `1` to override existing downloaded DB dump without asking.
VORTEX_DB_DOWNLOAD_FORCE=

# Set to `1` to print debug information in Vortex scripts.
VORTEX_DEBUG=

# Set to `y` to suppress Ahoy prompts.
AHOY_CONFIRM_RESPONSE=

# When Ahoy prompts are suppressed ($AHOY_CONFIRM_RESPONSE is 1), the command
# will wait for 3 seconds before proceeding.
# Set this variable to "1" to skip the wait.
AHOY_CONFIRM_WAIT_SKIP=1
