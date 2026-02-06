# Testing System Guide

## Overview

**BATS** - Unit testing individual shell scripts with mocked commands.
Tests scripts in isolation without real services. Fast execution (~seconds).

**PHPUnit** - Integration testing complete workflows in real Docker containers.
Tests actual Drupal site with real database and services. Slow execution (~minutes).

## Setup

```bash
cd .vortex
ahoy install  # Install dependencies (run once)
```

## BATS - Unit Testing Shell Scripts

**Use when**: Changed a shell script in `scripts/vortex/*.sh` or provision logic.

**Reference**: `node_modules/bats-helpers/README.md` - Mocking, assertions, step helpers

```bash
cd .vortex
ahoy test-bats -- tests/bats/unit/notify.bats  # Single script
ahoy test-bats -- tests/bats/unit/             # All unit tests
ahoy test-bats -- tests/bats/provision.bats    # Provision tests
ahoy test-bats -- tests/bats/                  # All BATS tests
```

### Helpers System

Located in `node_modules/bats-helpers/src/steps.bash`:

**Step Types**:
1. **Mock**: `@<command> [<args>] # <status> [ # <output> ]`
2. **Assert present**: `"<substring>"`
3. **Assert absent**: `"- <substring>"`

**Usage**:

```bash
declare -a STEPS=(
  "@drush -y status # 0 # success"  # Mock
  "Expected output"                  # Must contain
  "-      Unwanted output"           # Must NOT contain
)

mocks="$(run_steps "setup")"
# ... run code ...
run_steps "assert" "${mocks}"
```

### Key Files

- `provision.bats` - Provision script tests
- `_helper.bash` - Test helpers
- `unit/` - Individual script tests
- `fixtures/` - Test data

## PHPUnit - Integration Testing Workflows

**Use when**: Changed ahoy commands, Docker setup, or need to test full build/deploy workflow.

**References**:
- `vendor/alexskrypnyk/phpunit-helpers/README.md` - Test helpers, traits, assertions
- `vendor/alexskrypnyk/file/README.md` - File operations, batch processing

```bash
cd .vortex/tests
./vendor/bin/phpunit                          # All tests
./vendor/bin/phpunit --filter "TestClassName" # Specific test
```

### cmd() Function

Combines `processRun()` + `assertProcessSuccessful()` + assertions:

```php
$this->cmd('ahoy drush cr');                    // Simple
$this->cmd('ahoy info', 'Project name');        // With assertion
$this->cmd('ahoy info', ['line1', 'line2']);    // Multiple
$this->cmd('ahoy reset', inp: ['y'], tio: 300); // Named params
```

### cmdFail() Function

```php
$this->cmdFail('ahoy lint-be', tio: 120);
```

### Output Prefixes

**CRITICAL**: If using ANY prefix, ALL strings must have prefixes.

- `+` exact match present
- `*` substring present
- `-` exact match absent
- `!` substring absent

```php
// ✅ All prefixed
$this->cmd('ahoy info', ['* Xdebug', '! Enabled']);

// ❌ Mixed - throws exception
$this->cmd('ahoy info', ['Xdebug', '! Enabled']);
```

### Cross-Platform File Operations

Use `AlexSkrypnyk\File\File` class:

```php
use AlexSkrypnyk\File\File;

File::mkdir($path);
File::dump($path, $content);
File::remove($path);
File::rmdir($path);
```

Use `self::$tmp` for temporary files (auto-cleaned).

## Shell Script Patterns

### Standard Structure

```bash
#!/usr/bin/env bash
# Environment loading
t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && set +a && . "${t}" && rm "${t}"

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Variables with defaults
VAR="${VAR:-default}"

# Helpers
info() { printf "[INFO] %s\n" "${1}"; }
task() { printf "    > %s\n" "${1}"; }
note() { printf "      %s\n" "${1}"; }

# Main execution
```

### Test Maintenance

When updating scripts:

1. Update main script
2. Update BATS assertions
3. Run `ahoy update-snapshots` from `.vortex/`
