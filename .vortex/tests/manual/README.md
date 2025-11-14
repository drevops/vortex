# Manual Integration Tests

This directory contains manual test scripts for verifying Vortex notification integrations with external services.

## Purpose

These scripts are used to **manually verify** that notification integrations work correctly with real external services (Slack, JIRA, etc.) during development and testing. Unlike automated tests, these scripts actually send notifications to live services to confirm the integration and message formatting are correct.

## Available Scripts

### Slack Notifications (`try-slack-notification.sh`)

Manually send Slack webhook notifications to verify message formatting and delivery.

**Usage**:
```bash
# From project root
.vortex/tests/manual/try-slack-notification.sh [branch|pr]

# Try branch deployment notification
.vortex/tests/manual/try-slack-notification.sh branch

# Try PR deployment notification
.vortex/tests/manual/try-slack-notification.sh pr
```

**Configuration**:
- Requires webhook URL to be set: `export SLACK_WEBHOOK_URL="your-webhook-url"`

**What it tests**:
- Slack message formatting (rich attachments)
- Branch vs PR notification differences
- Field consistency and positioning
- Login link inclusion

---

### JIRA Notifications (`try-jira-notification.sh`)

Manually send JIRA notifications to verify comment posting, issue transitions, and assignments.

**Usage**:
```bash
# From project root
.vortex/tests/manual/try-jira-notification.sh [branch|pr]

# Try branch deployment notification
.vortex/tests/manual/try-jira-notification.sh branch

# Try PR deployment notification
.vortex/tests/manual/try-jira-notification.sh pr
```

**Configuration**:
- Requires API token to be set (email and endpoint have defaults)
- Set with environment variables:
  ```bash
  export JIRA_TOKEN="your-api-token"                              # Required
  export JIRA_USER="your-email@example.com"                       # Optional (default: alex@drevops.com)
  export JIRA_ENDPOINT="https://your-domain.atlassian.net"        # Optional (default: https://drevops.atlassian.net)
  export JIRA_ISSUE="PROJECT-123"                                 # Optional (default: DEMO-2)
  ```

**What it tests**:
- JIRA comment creation with unified message format
- Atlassian Document Format (ADF) rendering
- Code block formatting for branch/PR names
- Regular links (not inline cards)
- Issue transition to specified state
- Issue assignment to specified user

---

### New Relic Notifications (`try-newrelic-notification.sh`)

Manually send New Relic deployment notifications to verify message formatting and delivery.

**Usage**:
```bash
# From project root
.vortex/tests/manual/try-newrelic-notification.sh [branch|pr]

# Try branch deployment notification
.vortex/tests/manual/try-newrelic-notification.sh branch

# Try PR deployment notification
.vortex/tests/manual/try-newrelic-notification.sh pr
```

**Configuration**:
- Requires API key to be set (endpoint has default)
- Set with environment variables:
  ```bash
  export NEWRELIC_USER_KEY="your-api-key"                         # Required
  export NEWRELIC_APP_NAME="your-app-name"                        # Optional (default: Test Project-main)
  export NEWRELIC_ENDPOINT="https://api.newrelic.com/v2"          # Optional (default: shown)
  ```

**What it tests**:
- New Relic deployment marker creation
- Description field with unified message format
- Changelog field formatting
- Application ID auto-discovery
- Branch vs PR notification differences

---

### JIRA Authentication (`try-jira-auth.sh`)

Helper script to manually verify JIRA API authentication before trying full notifications.

**Usage**:
```bash
.vortex/tests/manual/try-jira-auth.sh
```

**What it tests**:
- API token validity
- User permissions
- Endpoint accessibility
- Account ID retrieval

---

### New Relic Authentication (`try-newrelic-auth.sh`)

Helper script to manually verify New Relic API authentication before trying full notifications.

**Usage**:
```bash
.vortex/tests/manual/try-newrelic-auth.sh
```

**What it tests**:
- REST API key validity
- Application list access
- Endpoint accessibility
- Available applications discovery

---

## When to Use These Scripts

### During Development
- **Refactoring notification formats**: Verify message appearance in real services
- **Adding new notification features**: Test integration with actual APIs
- **Debugging integration issues**: Isolate problems with external services

### Before Committing
- **Major notification changes**: Confirm formatting looks correct in live services
- **New notification channels**: Verify integration works end-to-end
- **API updates**: Test compatibility with service API changes

### Testing Scenarios
- **Branch deployments**: Standard deployment notifications
- **PR deployments**: Pull request-specific notifications
- **State transitions**: JIRA issue workflow changes
- **Assignment changes**: JIRA assignee updates

## Important Notes

### Real Service Impact
⚠️ **These scripts interact with real external services:**
- Slack: Sends actual messages to configured channels
- JIRA: Creates real comments, transitions issues, assigns users
- **Use test projects/channels** to avoid cluttering production systems

### Credentials and Security
🔐 **API tokens and webhooks**:
- **All scripts require credentials to be set via environment variables**
- Never commit credentials to version control
- Tokens should have minimal required permissions
- Use separate test accounts/webhooks for manual testing

### Test Data Cleanup
🧹 **Manual cleanup may be required**:
- Slack: Messages remain in channel (cannot be deleted by bot)
- JIRA: Comments, transitions, and assignments persist
- Consider using dedicated test issues/channels
- Document test runs to track manual test artifacts

## Script Architecture

All scripts follow a common pattern:

1. **Environment Setup**: Define test configuration and credentials
2. **Scenario Selection**: Choose between branch or PR deployment scenarios
3. **Variable Export**: Set Vortex notification environment variables
4. **Path Resolution**: Navigate to project root from script location
5. **Execution**: Run the actual notification script (`scripts/vortex/notify.sh`)
6. **Verification**: User manually confirms output in external service

## Integration with Automated Tests

While these manual tests verify real integrations, automated tests handle unit-level validation:

- **Automated BATS tests** (`.vortex/tests/bats/unit/notify-*.bats`): Mock external services, test script logic
- **Manual tests** (this directory): Use real services, verify message formatting and delivery

Both are necessary for comprehensive testing coverage.

## Troubleshooting

### Slack Webhook Errors
```bash
# Common issues:
# - Missing SLACK_WEBHOOK_URL environment variable
# - Invalid webhook URL format
# - Webhook revoked or expired
# - Channel access issues

# Verify webhook URL is set:
echo $SLACK_WEBHOOK_URL

# Test webhook manually:
curl -X POST -H 'Content-type: application/json' --data '{"text":"Test"}' $SLACK_WEBHOOK_URL
```

### JIRA Authentication Failures
```bash
# Common issues:
# - Missing JIRA_TOKEN environment variable
# - Expired API token
# - Insufficient permissions
# - Wrong endpoint URL

# Verify token is set:
echo $JIRA_TOKEN

# Test authentication:
.vortex/tests/manual/try-jira-auth.sh
```

### New Relic Authentication Failures
```bash
# Common issues:
# - Missing NEWRELIC_USER_KEY environment variable
# - Invalid or expired API key
# - Wrong API key type (need REST API / User API key)
# - Insufficient permissions

# Verify API key is set:
echo $NEWRELIC_USER_KEY

# Test authentication:
.vortex/tests/manual/try-newrelic-auth.sh

# Get your API key from:
# https://one.newrelic.com/launcher/api-keys-ui.api-keys-launcher
```

### Path Resolution Issues
```bash
# Scripts calculate project root automatically
# If issues occur, verify:
pwd                           # Check current directory
ls -la ./scripts/vortex/      # Verify project structure
```

## Adding New Manual Tests

When adding new notification integrations:

1. **Create test script** following naming pattern: `test-{service}-notification.sh`
2. **Include both scenarios**: branch and PR deployment notifications
3. **Document configuration**: Required environment variables and defaults
4. **Update this README**: Add usage instructions and what it tests
5. **Test thoroughly**: Verify with real service before committing

---

*These manual tests complement automated testing to ensure Vortex notification integrations work correctly with real external services.*
