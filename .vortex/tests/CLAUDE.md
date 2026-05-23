# Testing System Guide

## Overview

This directory holds **PHPUnit** integration tests that exercise complete
workflows in real Docker containers. Tests run against an actual Drupal site
with real database and services. Slow execution (~minutes).

**BATS** unit tests for shell scripts now live in the
`drevops/vortex-tooling` package at `.vortex/tooling/tests/`. See
`.vortex/tooling/CLAUDE.md` for shell-script test guidance.

## Setup

```bash
cd .vortex
ahoy install  # Install dependencies (run once)
```

## Commands

```bash
cd .vortex/installer

composer install      # Install dependencies
composer lint         # Run phpcs, phpstan, rector --dry-run
composer lint-fix     # Run rector, phpcbf
composer test         # Run tests (no coverage)
```

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

When updating PHPUnit tests:

1. Update test class
2. Run `ahoy update-snapshots` from `.vortex/` (regenerates installer fixtures)

For BATS tests covering shell scripts, see `.vortex/tooling/CLAUDE.md`.
