#!/usr/bin/env bash
##
# Additional environment variables used in this project in the Acquia environment.
# shellcheck disable=SC2034

# Skip copying of database between Acquia environment.
VORTEX_TASK_COPY_DB_ACQUIA_SKIP=

# Skip copying of files between Acquia environment.
VORTEX_TASK_COPY_FILES_ACQUIA_SKIP=

# Skip purging of edge cache in Acquia environment.
VORTEX_PURGE_CACHE_ACQUIA_SKIP=

# Skip Drupal site provisioning in Acquia environment.
VORTEX_PROVISION_ACQUIA_SKIP=

# NewRelic API key, usually of type 'USER'.
#
# @see https://www.vortextemplate.com/docs/workflows/notifications#new-relic
VORTEX_NOTIFY_NEWRELIC_APIKEY=

# JIRA API token.
#
# @see https://www.vortextemplate.com/docs/workflows/notifications#jira
VORTEX_NOTIFY_JIRA_TOKEN=

# GitHub token.
#
# @see https://www.vortextemplate.com/docs/workflows/notifications#github
VORTEX_NOTIFY_GITHUB_TOKEN=

# Slack webhook URL.
# The incoming Webhook URL from your Slack app configuration.
# @see https://www.vortextemplate.com/docs/workflows/notifications#slack
VORTEX_NOTIFY_SLACK_WEBHOOK="${VORTEX_NOTIFY_SLACK_WEBHOOK:-}"

# Custom webhook URL.
#
# @see https://www.vortextemplate.com/docs/workflows/notifications#webhook
VORTEX_NOTIFY_WEBHOOK_URL=
