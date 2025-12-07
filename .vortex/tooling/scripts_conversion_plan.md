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

### 📊 Test Statistics

- **Total Tests**: 247 tests, 1,258 assertions
- **Test Groups**:
  - `helpers` - 55 tests for core helper functions
  - `notify` - 132 tests for notification system
  - `default` - 60 tests for mock infrastructure & utilities
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
│   └── notify-webhook          # ✅ Generic webhook notifications
└── tests/
    ├── Exceptions/             # Custom exceptions for testing
    ├── Fixtures/               # Test fixture scripts
    ├── Self/                   # Mock infrastructure self-tests
    ├── Traits/
    │   └── MockTrait.php       # ✅ Comprehensive mock system
    └── Unit/
        ├── UnitTestCase.php    # Base test class
        ├── CommandExistsTest.php
        ├── DotenvTest.php
        ├── FormatterTest.php
        ├── GetenvTest.php
        ├── NotifyRouterTest.php
        ├── NotifyEmailTest.php
        ├── NotifySlackTest.php
        ├── NotifyGithubTest.php
        ├── NotifyJiraTest.php
        ├── NotifyNewrelicTest.php
        ├── NotifyWebhookTest.php
        ├── OverrideTest.php
        ├── RequestTest.php
        └── TokenTest.php
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

- **Minimize vertical space** - keep related code together without excessive blank lines

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
- CommandExistsTest.php - Command availability checking
- DotenvTest.php - Environment loading
- FormatterTest.php - Output formatting
- GetenvTest.php - Environment variable access
- OverrideTest.php - Script override system
- RequestTest.php - HTTP request functionality
- TokenTest.php - Token replacement

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

## Phase 5: Convert Remaining Scripts (TODO)

### High Priority (Frequently Used)
1. ⏳ `provision.sh` - Main provisioning
2. ⏳ `deploy.sh` - Main deployment router
3. ⏳ `deploy-artifact.sh`
4. ⏳ `deploy-lagoon.sh`
5. ⏳ `deploy-container-registry.sh`

### Medium Priority
6. ⏳ `download-db-*.sh` (url, ftp, lagoon)
7. ⏳ `export-db-image.sh`
8. ⏳ `provision-sanitize-db.sh`
9. ⏳ `task-copy-db-acquia.sh`
10. ⏳ `task-copy-files-acquia.sh`
11. ⏳ `task-purge-cache-acquia.sh`

### Lower Priority
12. ⏳ `login.sh`
13. ⏳ `login-container-registry.sh`
14. ⏳ `info.sh`
15. ⏳ `doctor.sh`
16. ⏳ `update-vortex.sh`

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

### 🔄 In Progress
- ⏳ High-priority scripts (provision, deploy, deploy-*)
- ⏳ Medium-priority scripts (download-db, export-db, etc.)
- ⏳ Lower-priority scripts (login, info, doctor, update-vortex)

### 📋 Todo
- ⏳ Package published to Packagist
- ⏳ Migration guide for existing projects
- ⏳ Integration with main Vortex template
- ⏳ CI/CD pipeline setup
- ⏳ Version 1.0.0 release

## Timeline Estimate

### Completed
- **Phase 1 (helpers.php)**: ✅ COMPLETE
- **Phase 2 (notify router)**: ✅ COMPLETE
- **Phase 3 (notify-slack)**: ✅ COMPLETE
- **Phase 4 (other notify scripts)**: ✅ COMPLETE

**Time invested**: ~2 weeks

### Remaining
- **Phase 5 (high-priority scripts)**: ~1 week
- **Phase 5 (medium-priority scripts)**: ~1 week
- **Phase 5 (lower-priority scripts)**: ~3 days
- **Final testing & documentation**: ~2 days

**Estimated remaining time**: ~3 weeks for complete conversion

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

**Last Updated**: 2025-11-23
**Current Phase**: Phase 5 (Remaining Scripts)
**Next Milestone**: Convert provision.sh and deploy.sh
