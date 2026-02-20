# Vortex Scripts Bash → PHP Conversion Plan

## Overview

Convert all Vortex bash scripts (`scripts/vortex/*.sh`) to PHP scripts as a standalone Composer package `drevops/vortex-tooling`.

## Current Status

### ✅ Completed Phases

**Phase 1: Foundation (helpers.php)** - COMPLETE
- ✅ 17 helper functions implemented (486 lines)
- ✅ Comprehensive test coverage
- ✅ Mock infrastructure fully operational

**Phase 2: Notification Router (notify)** - COMPLETE
- ✅ Main router script converted (80 lines)
- ✅ 100% test coverage (20 tests in NotifyRouterTest)
- ✅ Validates channels, events, and required variables
- ✅ Routes to channel-specific scripts

**Phase 3-4: All Notification Channel Scripts** - COMPLETE
- ✅ notify-email (172 lines, 13 tests)
- ✅ notify-slack (138 lines, 13 tests)
- ✅ notify-github (165 lines, 13 tests)
- ✅ notify-jira (271 lines, 41 tests)
- ✅ notify-newrelic (161 lines, 13 tests)
- ✅ notify-webhook (110 lines, 17 tests)
- **Total notification scripts: 1,097 lines, 130 tests**

**Phase 5: All Deployment Scripts** - COMPLETE
- ✅ deploy (150 lines, 14 tests)
- ✅ deploy-webhook (68 lines, 10 tests)
- ✅ deploy-artifact (130 lines, 15 tests)
- ✅ deploy-lagoon (290 lines, 12 tests)
- ✅ deploy-container-registry (120 lines, 15 tests)
- ✅ setup-ssh (180 lines, 14 tests)
- ✅ login-container-registry (85 lines, 8 tests)
- **Total deployment scripts: 1,023 lines, 88 tests**

### 📊 Test Statistics

- **Total Tests**: 429 tests, 1,861 assertions
- **Test Groups**:
  - `helpers` - 55 tests for core helper functions
  - `notify` - 132 tests for notification system
  - `deploy` - 74 tests for deployment system
  - `default` - 122 tests for mock infrastructure & utilities
- **All tests passing** ✅

### 📦 Package Structure

```
vortex-tooling/
├── composer.json               # Package configuration
├── phpunit.xml                 # PHPUnit configuration
├── CLAUDE.md                   # Development guide
├── scripts_conversion_plan.md  # This file
├── src/
│   ├── helpers.php             # ✅ Core helper functions (17 functions)
│   ├── notify                  # ✅ Notification router
│   ├── notify-email            # ✅ Email notifications
│   ├── notify-slack            # ✅ Slack notifications
│   ├── notify-github           # ✅ GitHub deployment notifications
│   ├── notify-jira             # ✅ JIRA integration
│   ├── notify-newrelic         # ✅ New Relic deployment markers
│   ├── notify-webhook          # ✅ Generic webhook notifications
│   ├── deploy                  # ✅ Deployment router
│   ├── deploy-artifact         # ✅ Git artifact deployment
│   ├── deploy-lagoon           # ✅ Lagoon deployment
│   ├── deploy-container-registry # ✅ Container registry deployment
│   ├── deploy-webhook          # ✅ Webhook deployment
│   ├── setup-ssh               # ✅ SSH configuration helper
│   └── login-container-registry # ✅ Docker registry login helper
└── tests/
    ├── Exceptions/             # Custom exceptions for testing
    ├── Fixtures/               # Test fixture scripts
    ├── Self/                   # Mock infrastructure self-tests
    ├── Traits/
    │   └── MockTrait.php       # ✅ Comprehensive mock system
    └── Unit/
        ├── UnitTestCase.php    # Base test class
        ├── HelpersCommandExistsTest.php
        ├── HelpersDotenvTest.php
        ├── HelpersFormatterTest.php
        ├── HelpersGetenvTest.php
        ├── NotifyRouterTest.php
        ├── NotifyEmailTest.php
        ├── NotifySlackTest.php
        ├── NotifyGithubTest.php
        ├── NotifyJiraTest.php
        ├── NotifyNewrelicTest.php
        ├── NotifyWebhookTest.php
        ├── HelpersOverrideTest.php
        ├── HelpersRequestTest.php
        ├── HelpersTokenTest.php
        ├── DeployTest.php            # ✅ Deployment router (14 tests)
        ├── DeployArtifactTest.php    # ✅ Git artifact deployment (15 tests)
        ├── DeployLagoonTest.php      # ✅ Lagoon deployment (12 tests)
        ├── DeployContainerRegistryTest.php # ✅ Container registry (15 tests)
        ├── DeployWebhookTest.php     # ✅ Webhook deployment (10 tests)
        ├── SetupSshTest.php          # ✅ SSH configuration (14 tests)
        └── LoginContainerRegistryTest.php # ✅ Docker registry login (8 tests)
```

## Requirements

### Script Structure
- **No file extensions** (e.g., `notify` not `notify.php`)
- **Shebang line**: `#!/usr/bin/env php`
- **Namespace**: `namespace DrevOps\VortexTooling;`
- **Strict types**: `declare(strict_types=1);`
- **Execute permissions**: `chmod +x`
- **Override support**: Via `VORTEX_TOOLING_CUSTOM_DIR` environment variable
- **Include helpers**: Every script includes `helpers.php`

### Coding Style

Scripts should be **readable but compact**:

- **Use ternary operators** for simple variable assignments with defaults
  ```php
  $var = getenv('VAR') ?: 'default';
  ```

- **Keep single-line code on one line** - no artificial line breaks for simple statements
  ```php
  // ✓ Good
  $project = getenv_required('VORTEX_NOTIFY_PROJECT');

  // ✗ Avoid unnecessary multi-line
  $project = getenv_required(
    'VORTEX_NOTIFY_PROJECT'
  );
  ```

- **Use array shorthand** where appropriate
  ```php
  $data = ['key' => 'value'];  // Not array('key' => 'value')
  ```

- **Prioritize clarity** - use descriptive variable names, avoid clever tricks

- **String quotes**: Use single quotes by default, double quotes only when necessary
  ```php
  // ✓ Good - single quotes for simple strings
  $var = 'default';
  info('Started notification');

  // ✓ Good - double quotes for interpolation
  info("Started $event notification");

  // ✓ Good - double quotes when string contains single quote
  $message = "It's an automated message";

  // ✗ Avoid - unnecessary double quotes
  $var = "default";
  info("Started notification");
  ```

## ✅ Phase 1: Foundation (helpers.php) - COMPLETE

### 1.1 Implemented Helper Functions

**Location:** `src/helpers.php` (486 lines, 17 functions)

#### Override System
```php
function execute_override(string $name): void
```
Checks for custom script override in `$VORTEX_TOOLING_CUSTOM_DIR` and executes if found.

#### Environment Loading
```php
function load_dotenv(array $env_files = ['.env']): void
```
Loads environment variables from .env files with support for quoted values and comments.

#### Environment Variable Helpers
```php
function getenv_default(...$args): string
function getenv_required(...$var_names): string
```
Safe environment variable access with defaults and validation.

#### Output Formatters
```php
function note(string $format, ...$args): void      // Plain note output
function task(string $format, ...$args): void      // [TASK] Blue output
function info(string $format, ...$args): void      // [INFO] Cyan output
function pass(string $format, ...$args): void      // [ OK ] Green output
function fail_no_exit(string $format, ...$args): void  // [FAIL] Red (no exit)
function fail(string $format, ...$args): void      // [FAIL] Red + exit(1)
```

All formatters support:
- Color detection (TERM env var and tty check)
- Printf-style formatting
- Fallback to plain text

#### Validation Functions
```php
function command_exists(string $command): bool
function term_supports_color(): bool
```

#### HTTP Request Functions
```php
function request_get(string $url, array $headers = [], int $timeout = 10): array
function request_post(string $url, $body = NULL, array $headers = [], int $timeout = 10): array
function request(string $url, array $options = []): array
```

**Return Format:**
```php
[
  'ok' => bool,           // TRUE if HTTP < 400
  'status' => int,        // HTTP status code
  'body' => string|false, // Response body
  'error' => ?string,     // cURL error message
  'info' => array,        // cURL info array
]
```

#### Utility Functions
```php
function replace_tokens(string $template, array $replacements): string
function is_debug(): bool
function quit(int $code = 0): void  // Wrapper around exit() for testing
```

### 1.2 Test Coverage - COMPLETE

**Tests**: 55 tests in `helpers` group
- HelpersCommandExistsTest.php - Command availability checking
- HelpersDotenvTest.php - Environment loading
- HelpersFormatterTest.php - Output formatting
- HelpersGetenvTest.php - Environment variable access
- HelpersOverrideTest.php - Script override system
- HelpersRequestTest.php - HTTP request functionality
- HelpersTokenTest.php - Token replacement

**Mock Infrastructure**: 60 tests in `default` group
- MockPassthruSelfTest.php - Shell command mocking
- MockQuitSelfTest.php - Exit/quit mocking
- MockRequestSelfTest.php - HTTP request mocking
- CheckNoExitSelfTest.php - Validate no direct exit() calls

## ✅ Phase 2: Notification Router (notify) - COMPLETE

### 2.1 Conversion Complete

**File:** `src/notify` (80 lines)

**Key features implemented:**
- Loads and validates environment variables
- Validates `VORTEX_NOTIFY_LABEL` (required)
- Validates `VORTEX_NOTIFY_EVENT` (must be pre_deployment or post_deployment)
- Defaults to `post_deployment` if event not specified
- Defaults to `email` channel if not specified
- Parses comma-separated channel list
- Filters empty channel names (handles `,,, ` input)
- Auto-generates `VORTEX_NOTIFY_LOGIN_URL` from `VORTEX_NOTIFY_ENVIRONMENT_URL`
- Routes to channel-specific scripts using `passthru()`
- Uses `quit()` instead of `exit()` for testability
- Proper error handling with exit codes

### 2.2 Test Coverage - COMPLETE

**Tests**: 20 tests in NotifyRouterTest.php

Coverage includes:
- Skip scenarios (VORTEX_NOTIFY_SKIP set, no channels specified)
- Missing required variables (VORTEX_NOTIFY_LABEL)
- Invalid event types (deployment, pre-deployment, postdeployment, invalid_event)
- Channel script errors (not found, not executable, exit fails)
- Successful notifications (default channel, single, multiple, with spaces)
- Valid event types (pre_deployment, post_deployment)
- Default values (event type, channels, login URL)
- Variable fallbacks (VORTEX_NOTIFY_PROJECT → VORTEX_PROJECT)
- Multiple channel execution

**Coverage**: 100% ✅

## ✅ Phase 3-4: All Notification Channel Scripts - COMPLETE

### 3.1 notify-email (172 lines) - COMPLETE

**Features:**
- Multiple recipients support (pipe-separated with optional names)
- Fallback to `DRUPAL_SITE_EMAIL` for sender
- Token replacement in message templates
- Uses `sendmail` or `mail` command
- HTML email with proper headers
- Pre-deployment event skipped

**Tests**: 13 tests
- Successful notification with sendmail
- Successful notification with mail command
- Multiple recipients handling
- Custom message templates
- Pre-deployment skip
- Missing mail command failure
- Missing required variables
- Fallback to generic variables
- Token replacement

### 3.2 notify-slack (138 lines) - COMPLETE

**Features:**
- Rich Slack attachments
- Custom webhook support
- Channel override
- Username and icon customization
- Color coding by event type
- Token replacement in messages
- URL sanitization

**Tests**: 13 tests
- Successful notification
- Custom message templates
- Pre-deployment event handling
- Missing required variables
- Fallback to generic variables
- Token replacement
- Custom bot configuration
- Webhook URL sanitization

### 3.3 notify-github (165 lines) - COMPLETE

**Features:**
- GitHub Deployments API integration
- Pre-deployment: Creates deployment
- Post-deployment: Updates deployment status
- Support for environment types (production, staging, development)
- Proper authentication with GitHub token
- Deployment ID persistence

**Tests**: 13 tests
- Successful pre-deployment notification
- Successful post-deployment notification
- Default environment type
- Invalid deployment ID handling
- Missing deployment for post-deployment
- Status update failures
- Missing required variables
- Fallback to generic GITHUB_TOKEN
- Missing environment URL for post-deployment
- Deployment ID validation edge cases

### 3.4 notify-jira (271 lines) - COMPLETE

**Features:**
- JIRA REST API integration
- Comment posting with Atlassian Document Format (ADF)
- Issue transition support
- Assignee management
- Token replacement in messages
- Pre-deployment event skipped
- Comprehensive error handling

**Tests**: 41 tests (most comprehensive)
- Comment-only notifications
- Notifications with transitions
- Notifications with assignee
- Pre-deployment skip
- Custom message templates
- Missing required variables
- Fallback to generic variables
- Token replacement
- URL sanitization
- Invalid credentials handling
- Transition ID validation
- Assignee validation
- HTTP error responses
- ADF format validation
- Multiple operations in sequence

### 3.5 notify-newrelic (161 lines) - COMPLETE

**Features:**
- New Relic Deployment Markers API
- Application ID or entity GUID support
- Revision/changelog/description fields
- User attribution
- Token replacement
- Pre-deployment event skipped

**Tests**: 13 tests
- Successful notification
- Custom description/changelog/revision
- Pre-deployment skip
- Missing required variables
- Fallback to generic variables
- Token replacement
- URL sanitization
- Invalid credentials
- Application ID vs entity GUID handling

### 3.6 notify-webhook (110 lines) - COMPLETE

**Features:**
- Generic webhook support
- Multiple HTTP methods (GET, POST, PUT, DELETE, PATCH)
- Custom headers (comma-separated)
- Custom payload template
- Token replacement
- Expected status code configuration
- Pre-deployment event skipped

**Tests**: 17 tests
- Multiple HTTP methods (POST, GET, PUT)
- Custom payload
- Multiple headers
- Token replacement with special characters
- URL sanitization
- Custom expected status codes
- HTTP request failures
- Missing required variables
- Fallback to generic variables
- Default payload structure

---

## ✅ Phase 5: Deployment Scripts - COMPLETE

### Overview

All deployment-related bash scripts have been converted to PHP. The deployment system consists of:

1. **Main Router** - `deploy.sh` → `deploy`
2. **Deployment Types** - Four specific deployment strategies
3. **Supporting Scripts** - SSH and registry login helpers

### Script Summary

| Script | PHP Lines | Tests | Status |
|--------|-----------|-------|--------|
| `deploy` | 150 | 14 | ✅ Complete |
| `deploy-webhook` | 68 | 10 | ✅ Complete |
| `login-container-registry` | 85 | 8 | ✅ Complete |
| `deploy-container-registry` | 120 | 15 | ✅ Complete |
| `setup-ssh` | 180 | 14 | ✅ Complete |
| `deploy-artifact` | 130 | 15 | ✅ Complete |
| `deploy-lagoon` | 290 | 12 | ✅ Complete |

**Phase 5 Totals:** ~1,023 PHP lines, 88 tests

### 5.1 deploy (Main Router) - COMPLETE

**Source:** `scripts/vortex/deploy.sh` (122 lines)

**Functionality:**
- Parse comma-separated deployment types (`VORTEX_DEPLOY_TYPES`)
- Skip logic via `VORTEX_DEPLOY_SKIP`
- PR skip list via `VORTEX_DEPLOY_SKIP_PRS`
- Branch skip list via `VORTEX_DEPLOY_SKIP_BRANCHES`
- Route to appropriate deployment script based on type:
  - `artifact` → `deploy-artifact`
  - `webhook` → `deploy-webhook`
  - `container_registry` → `deploy-container-registry`
  - `lagoon` → `deploy-lagoon`
- Set `VORTEX_DEPLOY_ARTIFACT_DST_BRANCH` for tag deployments

**Environment Variables:**
- `VORTEX_DEPLOY_TYPES` - Comma-separated deployment types (required)
- `VORTEX_DEPLOY_MODE` - `branch` or `tag` (default: `branch`)
- `VORTEX_DEPLOY_ACTION` - `deploy`, `deploy_override_db`, or `destroy`
- `VORTEX_DEPLOY_BRANCH` - Branch name
- `VORTEX_DEPLOY_PR` - PR number (without `pr-` prefix)
- `VORTEX_DEPLOY_SKIP` - Skip all deployments
- `VORTEX_DEPLOY_ALLOW_SKIP` - Enable PR/branch skip lists
- `VORTEX_DEPLOY_SKIP_PRS` - Comma-separated PR numbers to skip
- `VORTEX_DEPLOY_SKIP_BRANCHES` - Comma-separated branches to skip

**Test Scenarios:**
- Skip all deployments (VORTEX_DEPLOY_SKIP=1)
- Missing VORTEX_DEPLOY_TYPES
- Skip specific PR from skip list
- Skip specific branch from skip list
- Single deployment type (artifact, webhook, container_registry, lagoon)
- Multiple deployment types
- Tag mode sets artifact destination branch
- VORTEX_DEPLOY_ALLOW_SKIP flag behavior

**Implemented:** 150 PHP lines, 14 tests ✅

### 5.2 deploy-webhook - COMPLETE

**Source:** `scripts/vortex/deploy-webhook.sh` (54 lines)

**Functionality:**
- Simple HTTP call to webhook URL
- Configurable HTTP method (default: GET)
- Configurable expected response status (default: 200)
- Uses curl for HTTP requests

**Environment Variables:**
- `VORTEX_DEPLOY_WEBHOOK_URL` - Webhook URL (required)
- `VORTEX_DEPLOY_WEBHOOK_METHOD` - HTTP method (default: `GET`)
- `VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS` - Expected status (default: `200`)

**Test Scenarios:**
- Successful webhook call (GET)
- Successful webhook call (POST)
- Custom expected status code
- Missing required URL
- HTTP request failure
- Unexpected response status

**Implemented:** 68 PHP lines, 10 tests ✅

### 5.3 login-container-registry - COMPLETE

**Source:** `scripts/vortex/login-container-registry.sh` (63 lines)

**Functionality:**
- Login to Docker registry
- Skip if already logged in (checks config.json)
- Skip if credentials not provided
- Uses `docker login --password-stdin`

**Environment Variables:**
- `VORTEX_CONTAINER_REGISTRY` - Registry URL (default: `docker.io`)
- `VORTEX_CONTAINER_REGISTRY_USER` - Username
- `VORTEX_CONTAINER_REGISTRY_PASS` - Password
- `DOCKER_CONFIG` - Docker config directory (default: `~/.docker`)

**Test Scenarios:**
- Successful login
- Already logged in (skip)
- Missing credentials (skip)
- Empty registry name (fail)
- Docker command not available

**Implemented:** 85 PHP lines, 8 tests ✅

### 5.4 deploy-container-registry - COMPLETE

**Source:** `scripts/vortex/deploy-container-registry.sh` (105 lines)

**Functionality:**
- Parse service-to-image map (`service1=org/image1,service2=org/image2`)
- Login to container registry
- For each service:
  - Get container ID from running service
  - Commit container to image
  - Push image to registry
- Tag handling for images

**Environment Variables:**
- `VORTEX_DEPLOY_CONTAINER_REGISTRY_MAP` - Service/image map (required)
- `VORTEX_DEPLOY_CONTAINER_REGISTRY_IMAGE_TAG` - Image tag (default: `latest`)
- `VORTEX_DEPLOY_CONTAINER_REGISTRY` - Registry URL
- `VORTEX_DEPLOY_CONTAINER_REGISTRY_USER` - Username (required)
- `VORTEX_DEPLOY_CONTAINER_REGISTRY_PASS` - Password (required)

**Test Scenarios:**
- Successful deployment (single service)
- Successful deployment (multiple services)
- Empty map (skip deployment)
- Invalid map format
- Missing credentials
- Service not running
- Docker command failures
- Image tag handling (with/without tag in image name)

**Implemented:** 120 PHP lines, 15 tests ✅

### 5.5 setup-ssh - COMPLETE

**Source:** `scripts/vortex/setup-ssh.sh` (123 lines)

**Functionality:**
- Load SSH key based on prefix (`VORTEX_${PREFIX}_SSH_FINGERPRINT` or `VORTEX_${PREFIX}_SSH_FILE`)
- Convert SHA256 fingerprint to MD5 for file lookup
- Start SSH agent if not running
- Load key into SSH agent
- Optionally disable strict host key checking
- Export SSH file path variable

**Environment Variables:**
- `VORTEX_SSH_PREFIX` - Prefix for variable lookup (required)
- `VORTEX_${PREFIX}_SSH_FINGERPRINT` - SSH key fingerprint
- `VORTEX_${PREFIX}_SSH_FILE` - SSH key file path
- `VORTEX_SSH_REMOVE_ALL_KEYS` - Remove all keys before loading
- `VORTEX_SSH_DISABLE_STRICT_HOST_KEY_CHECKING` - Disable host key checking

**Test Scenarios:**
- Load key by file path
- Load key by MD5 fingerprint
- Load key by SHA256 fingerprint
- Key file set to `false` (skip)
- Key file not found
- SSH agent already running
- Key already loaded
- Start SSH agent
- Remove all keys before loading
- Disable strict host key checking

**Implemented:** 180 PHP lines, 14 tests ✅

### 5.6 deploy-artifact - COMPLETE

**Source:** `scripts/vortex/deploy-artifact.sh` (111 lines)

**Functionality:**
- Configure git user name/email
- Setup SSH via `setup-ssh`
- Install `drevops/git-artifact` composer package
- Copy `.git` and `.gitignore.artifact` to source
- Run git-artifact builder

**Environment Variables:**
- `VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE` - Remote repository URL (required)
- `VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME` - Git user name (default: `Deployment Robot`)
- `VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL` - Git user email (required)
- `VORTEX_DEPLOY_ARTIFACT_SRC` - Source directory (required)
- `VORTEX_DEPLOY_ARTIFACT_ROOT` - Root directory (default: `pwd`)
- `VORTEX_DEPLOY_ARTIFACT_DST_BRANCH` - Destination branch (default: `[branch]`)
- `VORTEX_DEPLOY_ARTIFACT_LOG` - Log file path
- `VORTEX_DEPLOY_SSH_FINGERPRINT` - SSH fingerprint
- `VORTEX_DEPLOY_SSH_FILE` - SSH file path

**Test Scenarios:**
- Successful artifact deployment
- Missing required variables
- Git config already set (skip)
- SSH setup via fingerprint
- SSH setup via file
- Artifact builder execution
- Path resolution (realpath)

**Implemented:** 130 PHP lines, 15 tests ✅

### 5.7 deploy-lagoon - COMPLETE

**Source:** `scripts/vortex/deploy-lagoon.sh` (269 lines) - Most Complex

**Functionality:**
- Install Lagoon CLI if not available
- Configure Lagoon instance
- Handle three actions: `deploy`, `deploy_override_db`, `destroy`
- PR deployments:
  - Check if environment exists (redeploy vs new)
  - Manage `VORTEX_PROVISION_OVERRIDE_DB` variable
  - Handle environment limit exceeded errors
- Branch deployments:
  - Similar to PR but with branch name
- Environment destruction

**Environment Variables:**
- `LAGOON_PROJECT` - Lagoon project name (required)
- `VORTEX_DEPLOY_BRANCH` - Branch name
- `VORTEX_DEPLOY_PR` - PR number
- `VORTEX_DEPLOY_PR_HEAD` - PR head branch
- `VORTEX_DEPLOY_PR_BASE_BRANCH` - PR base branch (default: `develop`)
- `VORTEX_DEPLOY_ACTION` - Action type (default: `create`)
- `VORTEX_DEPLOY_LAGOON_INSTANCE` - Instance name (default: `amazeeio`)
- `VORTEX_DEPLOY_LAGOON_INSTANCE_GRAPHQL` - GraphQL endpoint
- `VORTEX_DEPLOY_LAGOON_INSTANCE_HOSTNAME` - SSH hostname
- `VORTEX_DEPLOY_LAGOON_INSTANCE_PORT` - SSH port
- `VORTEX_DEPLOY_SSH_FINGERPRINT` - SSH fingerprint
- `VORTEX_DEPLOY_SSH_FILE` - SSH file path
- `VORTEX_LAGOONCLI_PATH` - CLI install path
- `VORTEX_LAGOONCLI_FORCE_INSTALL` - Force CLI install
- `VORTEX_LAGOONCLI_VERSION` - CLI version
- `VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED` - Fail on limit (default: `0`)

**Test Scenarios:**
- Destroy action
- PR deployment (new environment)
- PR deployment (redeploy)
- PR deployment with DB override
- Branch deployment (new environment)
- Branch deployment (redeploy)
- Branch deployment with DB override
- Missing required variables
- Lagoon CLI installation
- Environment limit exceeded (fail vs success)
- SSH setup integration
- Lagoon API interactions

**Implemented:** 290 PHP lines, 12 tests ✅

### Conversion Order (Completed)

All 7 deployment scripts will be converted together as a single milestone due to their interdependencies:

1. **deploy-webhook** - Simplest script, minimal dependencies
2. **login-container-registry** - Standalone utility (used by deploy-container-registry)
3. **deploy-container-registry** - Depends on login-container-registry
4. **setup-ssh** - Standalone utility (used by deploy-artifact, deploy-lagoon)
5. **deploy-artifact** - Depends on setup-ssh
6. **deploy-lagoon** - Most complex, depends on setup-ssh
7. **deploy** - Router that calls all deployment type scripts

**Dependency Graph:**
```
deploy (router)
├── deploy-webhook (standalone)
├── deploy-container-registry
│   └── login-container-registry
├── deploy-artifact
│   └── setup-ssh
└── deploy-lagoon
    └── setup-ssh
```

**Phase 5 Completed Totals:**
- **7 scripts** converted ✅
- **~1,023 PHP lines** total
- **88 tests** total

### New Helper Functions Added

Based on deployment script analysis, these helpers may be needed in `helpers.php`:

```php
// Execute shell commands with output capture
function shell_exec_with_status(string $command, ?int &$exit_code = null): string

// Check if file exists
function file_exists_check(string $path): bool

// Read file contents
function file_read(string $path): string

// Write file contents
function file_write(string $path, string $content): void

// Check if string contains substring (already in PHP: str_contains)
// No helper needed

// Parse key=value pairs from comma-separated string
function parse_key_value_map(string $map): array
```

### Mock System Extensions

For deployment scripts, we may need to extend MockTrait with:

```php
// Mock shell_exec() or exec() for command execution
function mockShellExec(string $command, string $output, int $exit_code = 0): void

// Mock file_exists()
function mockFileExists(string $path, bool $exists): void

// Mock file_get_contents()
function mockFileGetContents(string $path, string $content): void
```

---

## ✅ Phase 5.5: Sync PHP Scripts with Shell Updates - COMPLETE

After the initial conversion in Phases 1-5, the shell scripts received updates that
were not reflected in the PHP versions. This phase synchronized all differences.

### 5.5.1 Notification Scripts Sync

**notify (router):**
- Added `VORTEX_NOTIFY_BRANCH` (required) variable
- Added `VORTEX_NOTIFY_SHA` (required) variable
- Added `VORTEX_NOTIFY_PR_NUMBER` (optional) variable
- Changed `VORTEX_NOTIFY_ENVIRONMENT_URL` from optional to required
- Added `putenv()` exports for branch, SHA, PR number, label, environment URL, login URL
- Added detailed summary output matching shell format

**notify-slack:**
- Fixed field order: Deployment, Time, Environment, Login (matching shell)
- Added `footer: 'Vortex Deployment'` to Slack attachment

**notify-github:**
- Added `VORTEX_NOTIFY_GITHUB_BRANCH` (with fallback to `VORTEX_NOTIFY_BRANCH`) as deployment ref
- Replaced `$notify_label` with `$notify_branch` for all deployment API `ref` parameters
- Fixed `VORTEX_NOTIFY_GITHUB_ENVIRONMENT_TYPE` default: falls back to `VORTEX_NOTIFY_LABEL` then `'PR'`

**notify-jira:**
- Added `VORTEX_NOTIFY_JIRA_BRANCH` (with fallback to `VORTEX_NOTIFY_BRANCH`) for issue extraction
- Changed issue extraction regex to match against branch (not label)
- Replaced simple text-to-ADF with rich `build_adf_comment()` featuring clickable links, code marks, and hardBreaks

**notify-newrelic:**
- Reordered variables: enabled check first, then required variables
- Renamed `VORTEX_NOTIFY_NEWRELIC_USER_NAME` to `VORTEX_NOTIFY_NEWRELIC_USER` (matching shell)
- Added `VORTEX_NOTIFY_NEWRELIC_SHA` (with fallback to `VORTEX_NOTIFY_SHA`)
- Added SHA-based revision fallback before auto-generated LABEL-DATE-TIME pattern
- Added required variable validation after enabled check (user API key)
- Added numeric validation for app ID regardless of source

### 5.5.2 Deployment Scripts Sync

**deploy (router):**
- Fixed `VORTEX_DEPLOY_ACTION` default from `'deploy'` to `''` (matching shell)

**deploy-artifact:**
- Fixed SSH prefix from `DEPLOY` to `DEPLOY_ARTIFACT` (matching shell)
- Added `VORTEX_DEPLOY_ARTIFACT_SSH_FINGERPRINT` and `VORTEX_DEPLOY_ARTIFACT_SSH_FILE` variable names with fallback chain
- Updated git-artifact version from `~1.1` to `~1.2` (matching shell)

**deploy-lagoon:**
- Added tag deployment check: early exit for tag mode (`'Lagoon does not support tag deployments. Skipping.'`)
- Added `VORTEX_DEPLOY_MODE` variable
- Added `VORTEX_DEPLOY_LAGOON_*` prefixed variable support with fallbacks:
  - `VORTEX_DEPLOY_LAGOON_ACTION` (fallback: `VORTEX_DEPLOY_ACTION`, default: `'create'`)
  - `VORTEX_DEPLOY_LAGOON_PROJECT` (fallback: `LAGOON_PROJECT`)
  - `VORTEX_DEPLOY_LAGOON_BRANCH` (fallback: `VORTEX_DEPLOY_BRANCH`)
  - `VORTEX_DEPLOY_LAGOON_PR` (fallback: `VORTEX_DEPLOY_PR`)
  - `VORTEX_DEPLOY_LAGOON_PR_HEAD` (fallback: `VORTEX_DEPLOY_PR_HEAD`)
  - `VORTEX_DEPLOY_LAGOON_PR_BASE_BRANCH` (fallback: `VORTEX_DEPLOY_PR_BASE_BRANCH`)
  - `VORTEX_DEPLOY_LAGOON_SSH_FINGERPRINT` (fallback chain)
  - `VORTEX_DEPLOY_LAGOON_SSH_FILE` (fallback chain)
  - `VORTEX_DEPLOY_LAGOON_LAGOONCLI_PATH` (fallback: `VORTEX_LAGOONCLI_PATH`)
  - `VORTEX_DEPLOY_LAGOON_LAGOONCLI_FORCE_INSTALL` (fallback: `VORTEX_LAGOONCLI_FORCE_INSTALL`)
  - `VORTEX_DEPLOY_LAGOON_LAGOONCLI_VERSION` (fallback: `VORTEX_LAGOONCLI_VERSION`)
- Fixed SSH prefix from `DEPLOY` to `DEPLOY_LAGOON` (matching shell)

**login-container-registry:**
- Added `VORTEX_LOGIN_CONTAINER_REGISTRY` (fallback: `VORTEX_CONTAINER_REGISTRY`)
- Added `VORTEX_LOGIN_CONTAINER_REGISTRY_USER` (fallback: `VORTEX_CONTAINER_REGISTRY_USER`)
- Added `VORTEX_LOGIN_CONTAINER_REGISTRY_PASS` (fallback: `VORTEX_CONTAINER_REGISTRY_PASS`)
- Added `VORTEX_LOGIN_CONTAINER_REGISTRY_DOCKER_CONFIG` (fallback: `DOCKER_CONFIG`)

### 5.5.3 Test Updates

All test files updated to match script changes:
- `NotifyGithubTest.php` - Updated for branch variable, assertions, and data providers
- `NotifyJiraTest.php` - Updated for branch-based issue extraction
- `NotifyNewrelicTest.php` - Updated for optional variables, custom error messages
- `NotifyRouterTest.php` - Added required branch and SHA variables
- `DeployArtifactTest.php` - Updated git-artifact version in mocks
- `DeployLagoonTest.php` - Updated error message for new variable names

**All 429 tests passing with 1,861 assertions** ✅

---

## Phase 6: Convert Remaining Scripts (TODO)

### Database Scripts
1. ⏳ `download-db.sh` - Download database router
2. ⏳ `download-db-url.sh` - Download from URL
3. ⏳ `download-db-ftp.sh` - Download from FTP
4. ⏳ `download-db-container-registry.sh` - Download from container registry
5. ⏳ `download-db-lagoon.sh` - Download from Lagoon
6. ⏳ `download-db-acquia.sh` - Download from Acquia
7. ⏳ `download-db-s3.sh` - Download from S3
8. ⏳ `upload-db-s3.sh` - Upload to S3
9. ⏳ `export-db.sh` - Export database router
10. ⏳ `export-db-file.sh` - Export to file
11. ⏳ `export-db-image.sh` - Export to container image

### Provisioning Scripts
10. ⏳ `provision.sh` - Main provisioning
11. ⏳ `provision-sanitize-db.sh` - Database sanitization

### Acquia Task Scripts
12. ⏳ `task-copy-db-acquia.sh` - Copy database from Acquia
13. ⏳ `task-copy-files-acquia.sh` - Copy files from Acquia
14. ⏳ `task-purge-cache-acquia.sh` - Purge Acquia cache
15. ⏳ `task-custom-lagoon.sh` - Custom Lagoon tasks

### Utility Scripts
16. ⏳ `login.sh` - User login
17. ⏳ `logout.sh` - User logout
18. ⏳ `info.sh` - Project information
19. ⏳ `doctor.sh` - Environment diagnostics
20. ⏳ `reset.sh` - Reset environment
21. ⏳ `update-vortex.sh` - Update Vortex template

---

## Conversion Checklist (Per Script)

For each script being converted:

- [ ] Create new file without `.sh` extension
- [ ] Add shebang: `#!/usr/bin/env php`
- [ ] Add: `<?php` opening tag
- [ ] Add: `declare(strict_types=1);`
- [ ] Add: `namespace DrevOps\VortexTooling;`
- [ ] Add: `require_once __DIR__ . '/helpers.php';`
- [ ] Add: `execute_override(basename(__FILE__));`
- [ ] Replace bash env loading with environment variable access
- [ ] Convert color functions: `info()`, `task()`, `pass()`, `fail()`
- [ ] Convert variable assignments and defaults
- [ ] Convert conditionals: `[ ... ]` → `if (...)`
- [ ] Convert loops: `for ... do ... done` → `foreach (...) { ... }`
- [ ] Convert command execution: use `passthru()` or `exec()`
- [ ] Convert string operations
- [ ] Add exit code handling with `quit()` instead of `exit()`
- [ ] Set execute permission: `chmod +x`
- [ ] Create comprehensive test file
- [ ] Test in isolation
- [ ] Test with overrides
- [ ] Test with real environment variables
- [ ] Ensure 100% code coverage

## Bash → PHP Conversion Patterns

### Environment Variables
```bash
# Bash
VAR="${VAR:-default}"

# PHP
$var = getenv('VAR') ?: 'default';
```

### Environment Variable with Multiple Fallbacks
```bash
# Bash
VAR="${SPECIFIC_VAR:-${GENERIC_VAR}}"

# PHP
$var = getenv_default('SPECIFIC_VAR', 'GENERIC_VAR');
```

### Required Environment Variables
```bash
# Bash
: "${VAR:?Missing required variable}"

# PHP
$var = getenv_required('VAR');
```

### Conditionals
```bash
# Bash
if [ -z "${VAR}" ]; then
  echo "empty"
fi

# PHP
if (empty(getenv('VAR'))) {
  echo "empty\n";
}
```

### String Contains
```bash
# Bash
if [ -z "${CHANNELS##*email*}" ]; then
  # contains email
fi

# PHP
if (str_contains($channels, 'email')) {
  // contains email
}
```

### Command Execution
```bash
# Bash
./script.sh "$@"

# PHP
passthru('./script', $exit_code);
```

### Exit Codes
```bash
# Bash
command || exit 1

# PHP
passthru('command', $exit_code);
if ($exit_code !== 0) {
  quit($exit_code);
}
```

### Array Operations
```bash
# Bash
IFS=',' read -ra CHANNELS <<< "$VORTEX_NOTIFY_CHANNELS"

# PHP
$channels = explode(',', getenv('VORTEX_NOTIFY_CHANNELS'));
$channels = array_map('trim', $channels);
$channels = array_filter($channels); // Remove empty strings
```

### Key-Value Map Parsing (New for Deployment)
```bash
# Bash
IFS=',' read -r -a values <<<"${MAP}"
for value in "${values[@]}"; do
  IFS='=' read -r -a parts <<<"${value}"
  services+=("${parts[0]}")
  images+=("${parts[1]}")
done

# PHP
$pairs = explode(',', $map);
$services = [];
$images = [];
foreach ($pairs as $pair) {
  $parts = explode('=', trim($pair), 2);
  if (count($parts) !== 2) {
    fail("Invalid key/value pair: $pair");
  }
  $services[] = $parts[0];
  $images[] = $parts[1];
}
```

## Testing Strategy

### Unit Testing helpers.php
✅ **COMPLETE** - All helper functions fully tested:
- Color output functions with terminal detection
- Override system with custom directories
- Environment loading with .env files
- Validation helpers for commands and variables
- HTTP request functions with error handling
- Token replacement utilities

### Mock Infrastructure
✅ **COMPLETE** - Comprehensive mocking system:
- **MockTrait.php** - Queue-based mocking for:
  - `passthru()` - Shell command execution
  - `quit()`/`exit()` - Script termination
  - `request_*()` - HTTP requests (curl functions)
- **Self-tests** - Mock infrastructure validates itself
- **Automatic teardown** - Ensures all mocks are consumed

### Integration Testing
✅ **COMPLETE for notifications** - Each script tested with:
1. Mock environment variables
2. Success scenarios
3. Failure scenarios
4. Missing variable validation
5. Fallback variable handling
6. Token replacement
7. Output verification
8. Exit code validation

### Validation
✅ **COMPLETE for notifications**:
- All scripts maintain same behavior as bash versions
- Exit codes match original implementations
- Output format matches exactly
- Environment variable handling identical
- 100% code coverage achieved

## Package Setup (composer.json)

✅ **COMPLETE** - Current configuration:

```json
{
  "name": "drevops/vortex-tooling",
  "description": "Tooling for Vortex-based projects",
  "type": "library",
  "license": "GPL-2.0-or-later",
  "require-dev": {
    "php": ">=8.2",
    "alexskrypnyk/file": "^0.15",
    "alexskrypnyk/phpunit-helpers": "^0.14.0",
    "php-mock/php-mock-phpunit": "^2.14",
    "phpunit/phpunit": "^12.4.2",
    "phpstan/phpstan": "^2.1.31",
    "rector/rector": "^2.2.7",
    "drupal/coder": "^9@alpha",
    "drevops/phpcs-standard": "^0.5"
  },
  "autoload": {
    "psr-4": {
      "DrevOps\\VortexTooling\\": "src/"
    }
  },
  "scripts": {
    "check-no-exit": "php check-no-exit.php",
    "lint": [
      "phpcs",
      "phpstan --memory-limit=2G",
      "rector --dry-run",
      "@check-no-exit"
    ],
    "lint-fix": [
      "rector",
      "phpcbf"
    ],
    "test": "phpunit --no-coverage",
    "test-coverage": "php -d pcov.directory=. vendor/bin/phpunit"
  }
}
```

## Migration Path for Existing Projects

Projects using bash scripts can migrate gradually:

1. Install composer package: `composer require drevops/vortex-tooling`
2. Scripts available in `vendor/drevops/vortex-tooling/src/`
3. Update one caller at a time
4. Use override system (`VORTEX_TOOLING_CUSTOM_DIR`) for custom modifications
5. Remove old bash scripts when all converted

## Success Criteria

### ✅ Completed (Phases 1-4)
- ✅ helpers.php complete with all 17 functions
- ✅ All notification scripts converted and tested (7 scripts, 1,097 lines)
- ✅ Override system working and tested
- ✅ All 247 tests passing with 1,258 assertions
- ✅ 100% code coverage for all converted scripts
- ✅ Mock infrastructure complete and self-tested
- ✅ Comprehensive documentation in CLAUDE.md
- ✅ Package structure established
- ✅ Coding standards enforced (PHPCS, PHPStan, Rector)
- ✅ Special validation: No direct `exit()` calls (enforced by check-no-exit.php)

### ✅ Phase 5: Deployment Scripts - COMPLETE

All deployment scripts converted as a single milestone:

- ✅ deploy-webhook (68 PHP lines, 10 tests)
- ✅ login-container-registry (85 PHP lines, 8 tests)
- ✅ deploy-container-registry (120 PHP lines, 15 tests)
- ✅ setup-ssh (180 PHP lines, 14 tests)
- ✅ deploy-artifact (130 PHP lines, 15 tests)
- ✅ deploy-lagoon (290 PHP lines, 12 tests)
- ✅ deploy (150 PHP lines, 14 tests)

**Phase 5 Totals:** ~1,023 PHP lines, 88 tests

### 📋 Future (Phase 6)
- ⏳ Database download/export scripts
- ⏳ Provisioning scripts
- ⏳ Acquia task scripts
- ⏳ Utility scripts (login, info, doctor, reset, update-vortex)

### 📋 Release Preparation
- ⏳ Package published to Packagist
- ⏳ Migration guide for existing projects
- ⏳ Integration with main Vortex template
- ⏳ CI/CD pipeline setup
- ⏳ Version 1.0.0 release

## Key Learnings & Best Practices

### What Works Well

1. **Queue-based mocking** - Makes testing complex interactions manageable
2. **Self-testing mocks** - Validates mock infrastructure reliability
3. **Namespace usage** - Enables function mocking without conflicts
4. **quit() wrapper** - Allows testing scripts that would normally exit
5. **runScript() helper** - Simplifies testing complete scripts
6. **Data providers** - Efficiently tests multiple scenarios
7. **Environment trait** - Automatic cleanup prevents test pollution
8. **No direct exit()** - Enforced by automated check, prevents untestable code

### Coding Patterns to Follow

1. **Always use quit() instead of exit()** for testability
2. **Use getenv_default() for fallback chains** instead of nested ternaries
3. **Use getenv_required() for validation** instead of manual checks
4. **Keep scripts under 300 lines** - Split complex logic into functions
5. **Add type declarations** - `declare(strict_types=1);` on all files
6. **Use namespace consistently** - `namespace DrevOps\VortexTooling;`
7. **Include helpers.php first** - Before any other code
8. **Call execute_override() early** - Allow custom implementations

### Testing Patterns to Follow

1. **Test success scenarios first** - Establish baseline behavior
2. **Test failure scenarios** - Missing vars, invalid input, API errors
3. **Test fallback variables** - Ensure generic variables work
4. **Test token replacement** - Verify message customization
5. **Test edge cases** - Empty strings, special characters, boundary values
6. **Use descriptive test names** - Method name should explain what's tested
7. **Group related tests** - Use data providers for similar scenarios
8. **Assert on output** - Verify messages contain expected content
9. **Check exit codes** - Ensure proper error signaling

## Notes

- ✅ All notification scripts maintain 100% backward compatibility
- ✅ Output format exactly matches bash versions
- ✅ Exit codes identical to original implementations
- ✅ Environment variable handling preserved
- ✅ Override system tested and working
- Type hints added for PHP 8.2+ compatibility
- PHPStan level 9 enforced for maximum type safety
- Rector rules applied for code modernization
- PHPCS with Drupal standards for consistency
- Special validation prevents direct exit() usage

## Documentation

- ✅ **CLAUDE.md** - Comprehensive development guide with:
  - Package overview and structure
  - Core helper functions documentation
  - Testing architecture and best practices
  - Mock system detailed documentation
  - Development workflow guidelines
  - Common patterns and examples
  - Test naming conventions
  - Fixture creation guidelines

- ✅ **scripts_conversion_plan.md** - This file, tracking progress

- ⏳ **README.md** - Public-facing documentation (todo)
- ⏳ **MIGRATION.md** - Guide for existing projects (todo)

---

**Last Updated**: 2026-02-20
**Current Phase**: Phase 5.5 Sync Complete ✅
**Next Milestone**: Phase 6 - Database download/export scripts
