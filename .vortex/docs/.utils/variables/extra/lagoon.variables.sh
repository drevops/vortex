#!/usr/bin/env bash
##
# Additional environment variables used in this project in the Lagoon environment.
# shellcheck disable=SC2034

# Database name.
DATABASE_DATABASE=

# Enable New Relic in Lagoon environment.
#
# Set as project-wide variable.
NEWRELIC_ENABLED=

# New Relic license.
#
# Set as project-wide variable.
NEWRELIC_LICENSE=

# Notification NewRelic API key, usually of type 'USER'.
#
# @see https://www.vortextemplate.com/docs/deployment/notifications#new-relic
VORTEX_NOTIFY_NEWRELIC_APIKEY=

# Notification JIRA API token.
#
# @see https://www.vortextemplate.com/docs/deployment/notifications#jira
VORTEX_NOTIFY_JIRA_TOKEN=

# Notification GitHub token.
#
# @see https://www.vortextemplate.com/docs/deployment/notifications#github
VORTEX_NOTIFY_GITHUB_TOKEN=

# Notification Slack webhook URL.
# The incoming Webhook URL from your Slack app configuration.
# @see https://www.vortextemplate.com/docs/deployment/notifications#slack
VORTEX_NOTIFY_SLACK_WEBHOOK="${VORTEX_NOTIFY_SLACK_WEBHOOK:-}"

# Notification custom webhook URL.
#
# @see https://www.vortextemplate.com/docs/deployment/notifications#webhook
VORTEX_NOTIFY_WEBHOOK_URL=
